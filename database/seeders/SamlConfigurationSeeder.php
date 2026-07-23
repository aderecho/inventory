<?php

namespace Database\Seeders;

use App\Models\SamlConfiguration;
use Illuminate\Database\Seeder;
use RuntimeException;

class SamlConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        $entityId = trim((string) config('services.saml.idp_entity_id'));
        $ssoUrl = trim((string) config('services.saml.idp_sso_url'));
        $certificate = trim((string) config('services.saml.idp_public_cert'));

        if ($entityId === '' || $ssoUrl === '' || $certificate === '') {
            throw new RuntimeException('Set SAML_IDP_ENTITY_ID, SAML_IDP_SSO_URL, and SAML_IDP_PUBLIC_CERT before seeding SAML.');
        }

        SamlConfiguration::query()->update(['is_active' => false]);
        SamlConfiguration::updateOrCreate(
            ['entity_id' => $entityId],
            [
                'name' => config('services.saml.idp_name'),
                'sso_url' => $ssoUrl,
                'slo_url' => config('services.saml.idp_slo_url'),
                'x509_cert' => $certificate,
                'default_relay_state' => '/dashboard',
                'status' => 'active',
                'is_active' => true,
            ],
        );
    }
}
