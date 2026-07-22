<?php

namespace App\Http\Controllers;

use App\Models\SamlAuditEvent;
use App\Models\SamlConfiguration;
use App\Models\SamlReplayRecord;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use OneLogin\Saml2\Constants;
use OneLogin\Saml2\Response as SamlResponse;
use OneLogin\Saml2\Settings;

class SamlSpController extends Controller
{
    public function redirectToIdp(Request $request): RedirectResponse
    {
        $provider = SamlConfiguration::where('mode', 'idp')->where('is_active', true)->where('status', 'active')->first();
        if (! $provider) {
            return to_route('login')->withErrors(['sso' => 'SAML login is not configured.']);
        }

        $requestId = '_'.Str::uuid();
        SamlReplayRecord::create([
            'request_id' => $requestId,
            'issuer' => $provider->entity_id,
            'expires_at' => now()->addSeconds(config('services.saml.assertion_ttl_seconds')),
        ]);
        $this->audit($request, $provider, 'saml.sp.request.issued', 'success', ['request_id' => $requestId]);
        $provider->update(['last_used_at' => now()]);

        $relayState = $request->query('RelayState', $provider->default_relay_state ?: route('dashboard.index'));
        $query = http_build_query([
            'SAMLRequest' => $this->authnRequest($requestId, $provider),
            'RelayState' => $relayState,
        ]);

        return redirect()->away($provider->sso_url.(str_contains($provider->sso_url, '?') ? '&' : '?').$query);
    }

    public function acs(Request $request): RedirectResponse
    {
        $provider = SamlConfiguration::where('mode', 'idp')->where('is_active', true)->where('status', 'active')->first();
        if (! $provider) {
            return to_route('login')->withErrors(['sso' => 'SAML login is not configured.']);
        }

        try {
            $assertion = $this->validateResponse($request, $provider);
        } catch (\Throwable $exception) {
            $this->audit($request, $provider, 'saml.sp.assertion.rejected', 'rejected', [
                'reason' => $exception->getMessage(),
            ]);

            return to_route('login')->withErrors(['sso' => $exception->getMessage()]);
        }

        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($assertion['email'])])->first();
        if (! $user) {
            $this->audit($request, $provider, 'saml.sp.user.not_found', 'rejected', ['email' => $assertion['email']]);

            return to_route('saml.user-not-found')->with('saml_email', $assertion['email']);
        }

        if ((int) $user->status !== 1) {
            $this->audit($request, $provider, 'saml.sp.user.inactive', 'rejected', ['email' => $assertion['email']]);

            return to_route('saml.user-not-found')
                ->with('saml_email', $assertion['email'])
                ->with('saml_reason', 'inactive');
        }

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');
        $this->audit($request, $provider, 'saml.sp.assertion.accepted', 'success', [
            'user_id' => $user->id,
            'response_id' => $assertion['response_id'],
            'email' => $assertion['email'],
        ]);

        $relayState = (string) $request->input('RelayState');
        if ($relayState !== '' && str_starts_with($relayState, '/') && ! str_starts_with($relayState, '//')) {
            return redirect($relayState);
        }

        return $user->can('view dashboard') ? to_route('dashboard.index') : to_route('user.dashboard');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return to_route('login');
    }

    private function validateResponse(Request $request, SamlConfiguration $provider): array
    {
        $encoded = (string) $request->input('SAMLResponse');
        throw_if($encoded === '', new \RuntimeException('Missing SAMLResponse.'));
        $xml = base64_decode($encoded, true);
        throw_if($xml === false, new \RuntimeException('Invalid SAMLResponse encoding.'));

        $document = new DOMDocument();
        throw_unless(@$document->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING), new \RuntimeException('Invalid SAMLResponse XML.'));
        $xpath = new DOMXPath($document);
        $inResponseTo = $this->attribute($xpath, '/*[local-name()="Response"]', 'InResponseTo');
        if ($inResponseTo !== '') {
            throw_unless(
                SamlReplayRecord::where('request_id', $inResponseTo)->where('expires_at', '>', now())->exists(),
                new \RuntimeException('SAML response does not match an active login request.'),
            );
        }

        $this->syncServer($request);
        $response = new SamlResponse($this->settings($provider), $encoded);
        throw_unless($response->isValid($inResponseTo ?: null), new \RuntimeException($response->getError(false) ?: 'Invalid SAML signature or assertion.'));

        $responseId = $this->attribute($xpath, '/*[local-name()="Response"]', 'ID');
        $assertionId = $this->attribute($xpath, '//*[local-name()="Assertion"]', 'ID');
        throw_if($responseId === '' || $assertionId === '', new \RuntimeException('SAML response identifiers are missing.'));
        throw_if(SamlReplayRecord::where('response_id', $responseId)->orWhere('assertion_id', $assertionId)->exists(), new \RuntimeException('SAML response was already used.'));

        $email = trim((string) $response->getNameId());
        if ($email === '') {
            foreach (['email', 'mail', 'EmailAddress', 'emailAddress', 'urn:oid:0.9.2342.19200300.100.1.3'] as $name) {
                $email = trim((string) ($response->getAttributes()[$name][0] ?? ''));
                if ($email !== '') break;
            }
        }
        throw_if($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL), new \RuntimeException('SAML assertion did not include a valid email.'));

        if ($inResponseTo !== '') {
            SamlReplayRecord::where('request_id', $inResponseTo)->delete();
        }
        SamlReplayRecord::create([
            'assertion_id' => $assertionId,
            'response_id' => $responseId,
            'issuer' => $provider->entity_id,
            'expires_at' => now()->addSeconds(config('services.saml.assertion_ttl_seconds')),
        ]);

        return compact('responseId', 'assertionId', 'email') + ['response_id' => $responseId];
    }

    private function settings(SamlConfiguration $provider): Settings
    {
        return new Settings([
            'strict' => true,
            'debug' => config('app.debug'),
            'sp' => [
                'entityId' => config('services.saml.sp_entity_id'),
                'assertionConsumerService' => ['url' => url('/saml2/acs'), 'binding' => Constants::BINDING_HTTP_POST],
                'singleLogoutService' => ['url' => url('/saml2/logout'), 'binding' => Constants::BINDING_HTTP_REDIRECT],
            ],
            'idp' => [
                'entityId' => $provider->entity_id,
                'singleSignOnService' => ['url' => $provider->sso_url, 'binding' => Constants::BINDING_HTTP_REDIRECT],
                'singleLogoutService' => ['url' => $provider->slo_url ?: $provider->sso_url, 'binding' => Constants::BINDING_HTTP_REDIRECT],
                'x509cert' => $provider->x509_cert,
            ],
            'security' => [
                'wantXMLValidation' => true,
                'wantMessagesSigned' => $provider->sign_responses,
                'wantAssertionsSigned' => $provider->sign_assertions,
                'wantNameId' => true,
                'rejectUnsolicitedResponsesWithInResponseTo' => true,
                'destinationStrictlyMatches' => true,
            ],
        ]);
    }

    private function authnRequest(string $id, SamlConfiguration $provider): string
    {
        $instant = now()->utc()->format('Y-m-d\TH:i:s\Z');
        $issuer = htmlspecialchars((string) config('services.saml.sp_entity_id'), ENT_XML1);
        $destination = htmlspecialchars($provider->sso_url, ENT_XML1);
        $acs = htmlspecialchars(url('/saml2/acs'), ENT_XML1);
        $xml = <<<XML
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" ID="{$id}" Version="2.0" IssueInstant="{$instant}" Destination="{$destination}" AssertionConsumerServiceURL="{$acs}" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion">{$issuer}</saml:Issuer><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress" AllowCreate="true"/></samlp:AuthnRequest>
XML;
        return base64_encode(gzdeflate($xml));
    }

    private function attribute(DOMXPath $xpath, string $query, string $name): string
    {
        return $xpath->query($query)->item(0)?->attributes?->getNamedItem($name)?->nodeValue ?? '';
    }

    private function syncServer(Request $request): void
    {
        $appUrl = parse_url(rtrim((string) config('app.url'), '/')) ?: [];
        $scheme = $appUrl['scheme'] ?? ($request->isSecure() ? 'https' : 'http');
        $host = $appUrl['host'] ?? $request->getHost();
        $port = $appUrl['port'] ?? ($scheme === 'https' ? 443 : 80);

        $_SERVER['REQUEST_URI'] = parse_url(url('/saml2/acs'), PHP_URL_PATH) ?: '/saml2/acs';
        $_SERVER['SCRIPT_NAME'] = '';
        $_SERVER['QUERY_STRING'] = '';
        $_SERVER['HTTP_HOST'] = $host.($port === 80 || $port === 443 ? '' : ':'.$port);
        $_SERVER['SERVER_NAME'] = $host;
        $_SERVER['SERVER_PORT'] = (string) $port;
        $_SERVER['HTTPS'] = $scheme === 'https' ? 'on' : 'off';
        $_SERVER['REQUEST_SCHEME'] = $scheme;
    }

    private function audit(Request $request, ?SamlConfiguration $provider, string $event, string $outcome, array $data = []): void
    {
        SamlAuditEvent::create([
            'saml_configuration_id' => $provider?->id,
            'event_name' => $event,
            'entity_id' => $provider?->entity_id,
            'user_id' => $data['user_id'] ?? null,
            'request_id' => $data['request_id'] ?? null,
            'response_id' => $data['response_id'] ?? null,
            'ip_address' => $request->ip(),
            'outcome' => $outcome,
            'metadata' => $data,
        ]);
    }
}
