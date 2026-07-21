<?php

namespace Tests\Feature;

use App\Models\SamlConfiguration;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;

class SamlConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sp_metadata_publishes_the_expected_endpoints_without_a_certificate(): void
    {
        config()->set('services.saml.sp_entity_id', 'https://inventory.example.edu/saml2/metadata');

        $response = $this->get(route('saml.metadata'));

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/samlmetadata+xml')
            ->assertSee('https://inventory.example.edu/saml2/metadata', false)
            ->assertSee(url('/saml2/acs'), false)
            ->assertSee(url('/saml2/logout'), false)
            ->assertDontSee('BEGIN CERTIFICATE', false);
    }

    public function test_saml_login_creates_a_correlated_request_and_redirects_to_the_active_idp(): void
    {
        $configuration = SamlConfiguration::create([
            'name' => 'UP Cebu AMS',
            'entity_id' => 'https://ams.upcebu.edu.ph/saml2/metadata',
            'sso_url' => 'https://ams.upcebu.edu.ph/saml2/sso',
            'slo_url' => 'https://ams.upcebu.edu.ph/saml2/slo',
            'x509_cert' => 'test-certificate',
            'default_relay_state' => '/dashboard',
            'status' => 'active',
            'is_active' => true,
        ]);

        $response = $this->get(route('saml.login'));

        $response->assertRedirectContains($configuration->sso_url);
        $this->assertDatabaseHas('saml_replay_records', [
            'issuer' => $configuration->entity_id,
        ]);
        $this->assertDatabaseHas('saml_audit_events', [
            'event_name' => 'saml.sp.request.issued',
            'outcome' => 'success',
        ]);
    }

    public function test_authorized_user_can_manage_saml_configurations(): void
    {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('view roles', 'web');
        $user->givePermissionTo($permission);

        $payload = [
            'name' => 'Test IdP',
            'entity_id' => 'https://idp.example.edu/metadata',
            'sso_url' => 'https://idp.example.edu/sso',
            'slo_url' => 'https://idp.example.edu/slo',
            'x509_cert' => 'test-certificate',
            'default_relay_state' => '/dashboard',
            'status' => 'active',
            'is_active' => true,
        ];

        $create = $this->actingAs($user)->postJson(route('saml_configurations.store'), $payload);
        $create->assertCreated()
            ->assertJsonPath('configuration.name', 'Test IdP')
            ->assertJsonPath('configuration.metadata_source', 'generated')
            ->assertJsonFragment(['entity_id' => 'https://idp.example.edu/metadata']);

        $this->assertStringContainsString(
            '<md:EntityDescriptor',
            $create->json('configuration.metadata_xml'),
        );

        $configuration = SamlConfiguration::firstOrFail();
        $this->actingAs($user)
            ->putJson(route('saml_configurations.update', $configuration), [...$payload, 'name' => 'Updated IdP'])
            ->assertOk()
            ->assertJsonPath('configuration.name', 'Updated IdP');

        $this->actingAs($user)
            ->deleteJson(route('saml_configurations.destroy', $configuration))
            ->assertOk();

        $this->assertDatabaseEmpty('saml_configurations');
    }

    public function test_authorized_user_can_import_idp_metadata_xml(): void
    {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate('view roles', 'web');
        $user->givePermissionTo($permission);

        $metadata = <<<'XML'
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="https://ams.upcebu.edu.ph/saml2/metadata">
  <md:IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
    <md:KeyDescriptor use="signing"><ds:KeyInfo xmlns:ds="http://www.w3.org/2000/09/xmldsig#"><ds:X509Data><ds:X509Certificate>QUJD</ds:X509Certificate></ds:X509Data></ds:KeyInfo></md:KeyDescriptor>
    <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://ams.upcebu.edu.ph/saml2/slo"/>
    <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="https://ams.upcebu.edu.ph/saml2/sso"/>
  </md:IDPSSODescriptor>
XML;

        $response = $this->actingAs($user)->postJson(route('saml_configurations.store'), [
            'name' => 'UP Cebu AMS',
            'metadata_xml' => $metadata,
            'status' => 'active',
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('configuration.entity_id', 'https://ams.upcebu.edu.ph/saml2/metadata')
            ->assertJsonPath('configuration.sso_url', 'https://ams.upcebu.edu.ph/saml2/sso')
            ->assertJsonPath('configuration.slo_url', 'https://ams.upcebu.edu.ph/saml2/slo');

        $this->assertDatabaseHas('saml_configurations', [
            'entity_id' => 'https://ams.upcebu.edu.ph/saml2/metadata',
            'metadata_xml' => $metadata."\n</md:EntityDescriptor>",
        ]);
    }

    public function test_inventory_accepts_oneportal_response_signed_idp_initiated_login(): void
    {
        config()->set('app.url', 'http://localhost:8001');
        config()->set('services.saml.sp_entity_id', 'http://localhost:8001/saml2/metadata');
        [$privateKey, $certificate] = $this->samlSigningMaterial();

        SamlConfiguration::create([
            'name' => 'OnePortal Local IdP',
            'slug' => 'oneportal-local-idp',
            'mode' => 'idp',
            'entity_id' => 'http://127.0.0.1:8012/saml2/metadata',
            'sso_url' => 'http://127.0.0.1:8012/saml2/sso',
            'x509_cert' => $certificate,
            'signing_algo' => 'rsa-sha256',
            'sign_responses' => true,
            'sign_assertions' => false,
            'status' => 'active',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'email' => 'standard.user@oneportal.test',
            'status' => 1,
        ]);

        $response = $this
            ->withServerVariables([
                'HTTP_HOST' => 'localhost:8001',
                'SERVER_NAME' => 'localhost',
                'SERVER_PORT' => '8001',
            ])
            ->post(route('saml.acs'), [
                'SAMLResponse' => base64_encode($this->onePortalResponse($privateKey, $certificate, $user->email)),
                'RelayState' => '/dashboard',
            ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('saml_audit_events', [
            'event_name' => 'saml.sp.assertion.accepted',
            'outcome' => 'success',
            'user_id' => $user->id,
        ]);
    }

    private function samlSigningMaterial(): array
    {
        $privateKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($privateKey, $privateKeyPem);
        $csr = openssl_csr_new(['commonName' => 'OnePortal Test IdP'], $privateKey);
        $signedCertificate = openssl_csr_sign($csr, null, $privateKey, 365);
        openssl_x509_export($signedCertificate, $certificatePem);

        return [$privateKeyPem, $certificatePem];
    }

    private function onePortalResponse(string $privateKey, string $certificate, string $email): string
    {
        $now = now()->utc();
        $expires = $now->copy()->addMinutes(5);
        $responseId = '_'.str()->uuid();
        $assertionId = '_'.str()->uuid();
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="{$responseId}" Version="2.0" IssueInstant="{$now->toIso8601ZuluString()}" Destination="http://localhost:8001/saml2/acs">
  <saml:Issuer>http://127.0.0.1:8012/saml2/metadata</saml:Issuer>
  <samlp:Status><samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/></samlp:Status>
  <saml:Assertion ID="{$assertionId}" Version="2.0" IssueInstant="{$now->toIso8601ZuluString()}">
    <saml:Issuer>http://127.0.0.1:8012/saml2/metadata</saml:Issuer>
    <saml:Subject>
      <saml:NameID Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress">{$email}</saml:NameID>
      <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer"><saml:SubjectConfirmationData Recipient="http://localhost:8001/saml2/acs" NotOnOrAfter="{$expires->toIso8601ZuluString()}"/></saml:SubjectConfirmation>
    </saml:Subject>
    <saml:Conditions NotBefore="{$now->copy()->subMinute()->toIso8601ZuluString()}" NotOnOrAfter="{$expires->toIso8601ZuluString()}"><saml:AudienceRestriction><saml:Audience>http://localhost:8001/saml2/metadata</saml:Audience></saml:AudienceRestriction></saml:Conditions>
    <saml:AuthnStatement AuthnInstant="{$now->toIso8601ZuluString()}" SessionIndex="{$responseId}"><saml:AuthnContext><saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef></saml:AuthnContext></saml:AuthnStatement>
  </saml:Assertion>
</samlp:Response>
XML;

        $document = new \DOMDocument();
        $document->preserveWhiteSpace = false;
        $document->loadXML($xml);
        $response = $document->documentElement;
        $issuer = $response->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Issuer')->item(0);
        $signature = new XMLSecurityDSig();
        $signature->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);
        $signature->addReference(
            $response,
            XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N],
            ['id_name' => 'ID', 'force_uri' => true],
        );
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey, false);
        $signature->sign($key);
        $signature->add509Cert($certificate, true, false);
        $signature->insertSignature($response, $issuer?->nextSibling);

        return $document->saveXML();
    }
}
