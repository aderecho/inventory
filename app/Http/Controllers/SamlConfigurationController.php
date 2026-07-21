<?php

namespace App\Http\Controllers;

use App\Models\SamlAuditEvent;
use App\Models\SamlConfiguration;
use DOMDocument;
use DOMXPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SamlConfigurationController extends Controller
{
    public function index()
    {
        return Inertia::render('SamlConfiguration', [
            'configurations' => SamlConfiguration::latest()->get()->map(fn ($config) => $this->transform($config)),
            'endpoints' => [
                'metadata' => route('saml.metadata'),
                'acs' => route('saml.acs'),
                'logout' => route('saml.logout'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateConfiguration($request);

        $configuration = DB::transaction(function () use ($validated) {
            if ($validated['is_active']) {
                SamlConfiguration::query()->update(['is_active' => false]);
                $validated['status'] = 'active';
            }

            return SamlConfiguration::create($validated);
        });

        return response()->json([
            'message' => 'SAML configuration created successfully.',
            'configuration' => $this->transform($configuration),
        ], 201);
    }

    public function update(Request $request, SamlConfiguration $samlConfiguration)
    {
        $validated = $this->validateConfiguration($request, $samlConfiguration);

        DB::transaction(function () use ($validated, $samlConfiguration) {
            if ($validated['is_active']) {
                SamlConfiguration::whereKeyNot($samlConfiguration->id)->update(['is_active' => false]);
                $validated['status'] = 'active';
            } elseif ($validated['status'] === 'inactive') {
                $validated['is_active'] = false;
            }
            $samlConfiguration->update($validated);
        });

        return response()->json([
            'message' => 'SAML configuration updated successfully.',
            'configuration' => $this->transform($samlConfiguration->fresh()),
        ]);
    }

    public function destroy(SamlConfiguration $samlConfiguration)
    {
        $samlConfiguration->delete();

        return response()->json(['message' => 'SAML configuration deleted successfully.']);
    }

    private function validateConfiguration(Request $request, ?SamlConfiguration $configuration = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'mode' => ['nullable', Rule::in(['idp', 'sp'])],
            'entity_id' => ['nullable', 'string', 'max:2048'],
            'sso_url' => ['nullable', 'url:http,https', 'max:2048'],
            'acs_url' => ['nullable', 'url:http,https', 'max:2048'],
            'slo_url' => ['nullable', 'url:http,https', 'max:2048'],
            'x509_cert' => ['nullable', 'string'],
            'signing_algo' => ['nullable', Rule::in(['rsa-sha256', 'rsa-sha384', 'rsa-sha512'])],
            'metadata_xml' => ['nullable', 'string', 'max:1000000'],
            'default_relay_state' => ['nullable', 'string', 'max:2048'],
            'attribute_release' => ['nullable', 'array'],
            'attribute_release.*' => ['string', 'max:255'],
            'require_signed_requests' => ['nullable', 'boolean'],
            'sign_responses' => ['nullable', 'boolean'],
            'sign_assertions' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['draft', 'active', 'inactive'])],
            'is_active' => ['required', 'boolean'],
        ]);

        $validated += [
            'slug' => $configuration?->slug ?: $this->uniqueSlug($validated['name'], $configuration),
            'mode' => $configuration?->mode ?: 'idp',
            'acs_url' => $configuration?->acs_url,
            'signing_algo' => $configuration?->signing_algo ?: 'rsa-sha256',
            'attribute_release' => $configuration?->attribute_release ?: [
                'email', 'first_name', 'last_name', 'display_name', 'role', 'department',
            ],
            'require_signed_requests' => $configuration?->require_signed_requests ?? false,
            'sign_responses' => $configuration?->sign_responses ?? true,
            'sign_assertions' => $configuration?->sign_assertions ?? false,
            'notes' => $configuration?->notes,
        ];

        if (filled($validated['metadata_xml'] ?? null)) {
            $validated['metadata_xml'] = $this->normalizeMetadataXml($validated['metadata_xml']);
            $validated = array_replace($validated, array_filter(
                $this->parseMetadata($validated['metadata_xml']),
                fn ($value) => filled($value),
            ));
        }

        Validator::make($validated, [
            'entity_id' => ['required', 'string', 'max:2048', Rule::unique('saml_configurations')->ignore($configuration?->id)],
            'sso_url' => ['required', 'url:http,https', 'max:2048'],
            'x509_cert' => ['required', 'string'],
        ])->validate();

        return $validated;
    }

    private function uniqueSlug(string $name, ?SamlConfiguration $configuration = null): string
    {
        $base = Str::slug($name) ?: 'saml-provider';
        $slug = $base;
        $suffix = 2;

        while (SamlConfiguration::query()
            ->where('slug', $slug)
            ->when($configuration, fn ($query) => $query->whereKeyNot($configuration->id))
            ->exists()) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }

    private function parseMetadata(string $metadataXml): array
    {
        $metadataXml = $this->normalizeMetadataXml($metadataXml);
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($metadataXml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            abort(422, 'The IdP metadata XML could not be parsed.');
        }

        $xpath = new DOMXPath($document);
        $certificate = trim((string) $xpath->evaluate('string(//*[local-name()="IDPSSODescriptor"]//*[local-name()="KeyDescriptor"][@use="signing" or not(@use)][1]//*[local-name()="X509Certificate"][1])'));
        if ($certificate === '') {
            $certificate = trim((string) $xpath->evaluate('string(//*[local-name()="X509Certificate"][1])'));
        }

        return [
            'entity_id' => trim((string) $xpath->evaluate('string(//*[local-name()="EntityDescriptor"][1]/@entityID)')),
            'sso_url' => trim((string) $xpath->evaluate('string(//*[local-name()="IDPSSODescriptor"]/*[local-name()="SingleSignOnService"][@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"][1]/@Location)'))
                ?: trim((string) $xpath->evaluate('string(//*[local-name()="SingleSignOnService"][1]/@Location)')),
            'slo_url' => trim((string) $xpath->evaluate('string(//*[local-name()="SingleLogoutService"][1]/@Location)')),
            'x509_cert' => $certificate === '' ? '' : "-----BEGIN CERTIFICATE-----\n".preg_replace('/\s+/', '', $certificate)."\n-----END CERTIFICATE-----",
        ];
    }

    private function normalizeMetadataXml(string $metadataXml): string
    {
        $metadataXml = trim($metadataXml);

        if (str_starts_with($metadataXml, '```')) {
            $metadataXml = preg_replace('/^```(?:xml)?\s*/i', '', $metadataXml) ?? $metadataXml;
            $metadataXml = preg_replace('/\s*```$/', '', $metadataXml) ?? $metadataXml;
            $metadataXml = trim($metadataXml);
        }

        $hasEntityDescriptor = preg_match('/<(?:[A-Za-z_][\w.-]*:)?EntityDescriptor\b/', $metadataXml) === 1;
        $hasClosingEntityDescriptor = preg_match('/<\/(?:[A-Za-z_][\w.-]*:)?EntityDescriptor\s*>/', $metadataXml) === 1;

        if ($hasEntityDescriptor && ! $hasClosingEntityDescriptor) {
            $prefix = '';
            if (preg_match('/<([A-Za-z_][\w.-]*):EntityDescriptor\b/', $metadataXml, $matches) === 1) {
                $prefix = $matches[1].':';
            }

            $metadataXml .= "\n</{$prefix}EntityDescriptor>";
        }

        return $metadataXml;
    }

    private function transform(SamlConfiguration $configuration): array
    {
        $lastSuccessfulLogin = SamlAuditEvent::query()
            ->where('saml_configuration_id', $configuration->id)
            ->where('event_name', 'saml.sp.assertion.accepted')
            ->where('outcome', 'success')
            ->latest()
            ->first();

        return [
            'id' => $configuration->id,
            'name' => $configuration->name,
            'slug' => $configuration->slug,
            'mode' => $configuration->mode,
            'entity_id' => $configuration->entity_id,
            'sso_url' => $configuration->sso_url,
            'acs_url' => $configuration->acs_url,
            'slo_url' => $configuration->slo_url,
            'x509_cert' => $configuration->x509_cert,
            'signing_algo' => $configuration->signing_algo,
            'metadata_xml' => $this->metadataXmlFor($configuration),
            'metadata_source' => filled($configuration->metadata_xml) ? 'imported' : 'generated',
            'default_relay_state' => $configuration->default_relay_state,
            'attribute_release' => $configuration->attribute_release,
            'require_signed_requests' => $configuration->require_signed_requests,
            'sign_responses' => $configuration->sign_responses,
            'sign_assertions' => $configuration->sign_assertions,
            'notes' => $configuration->notes,
            'status' => $configuration->status,
            'is_active' => $configuration->is_active,
            'last_used_at' => $configuration->last_used_at?->format('M j, Y g:i A'),
            'last_successful_login_at' => $lastSuccessfulLogin?->created_at?->format('M j, Y g:i A'),
            'updated_at' => $configuration->updated_at?->format('M j, Y g:i A'),
        ];
    }

    private function metadataXmlFor(SamlConfiguration $configuration): string
    {
        if (filled($configuration->metadata_xml)) {
            return $configuration->metadata_xml;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $document->formatOutput = true;

        $entity = $document->createElementNS(
            'urn:oasis:names:tc:SAML:2.0:metadata',
            'md:EntityDescriptor',
        );
        $entity->setAttribute('entityID', $configuration->entity_id);
        $document->appendChild($entity);

        $idp = $document->createElement('md:IDPSSODescriptor');
        $idp->setAttribute('protocolSupportEnumeration', 'urn:oasis:names:tc:SAML:2.0:protocol');
        $entity->appendChild($idp);

        if (filled($configuration->x509_cert)) {
            $keyDescriptor = $document->createElement('md:KeyDescriptor');
            $keyDescriptor->setAttribute('use', 'signing');
            $keyInfo = $document->createElementNS('http://www.w3.org/2000/09/xmldsig#', 'ds:KeyInfo');
            $x509Data = $document->createElement('ds:X509Data');
            $certificate = preg_replace(
                '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/',
                '',
                $configuration->x509_cert,
            );
            $x509Data->appendChild($document->createElement('ds:X509Certificate', $certificate));
            $keyInfo->appendChild($x509Data);
            $keyDescriptor->appendChild($keyInfo);
            $idp->appendChild($keyDescriptor);
        }

        $idp->appendChild($document->createElement(
            'md:NameIDFormat',
            'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
        ));

        if (filled($configuration->slo_url)) {
            $slo = $document->createElement('md:SingleLogoutService');
            $slo->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect');
            $slo->setAttribute('Location', $configuration->slo_url);
            $idp->appendChild($slo);
        }

        $sso = $document->createElement('md:SingleSignOnService');
        $sso->setAttribute('Binding', 'urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect');
        $sso->setAttribute('Location', $configuration->sso_url);
        $idp->appendChild($sso);

        return $document->saveXML();
    }
}
