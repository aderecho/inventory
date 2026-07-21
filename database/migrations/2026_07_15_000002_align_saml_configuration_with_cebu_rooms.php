<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('saml_configurations', function (Blueprint $table) {
            if (! Schema::hasColumn('saml_configurations', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            if (! Schema::hasColumn('saml_configurations', 'mode')) {
                $table->string('mode')->default('idp')->after('slug');
            }
            if (! Schema::hasColumn('saml_configurations', 'acs_url')) {
                $table->string('acs_url')->nullable()->after('sso_url');
            }
            if (! Schema::hasColumn('saml_configurations', 'signing_algo')) {
                $table->string('signing_algo')->default('rsa-sha256')->after('x509_cert');
            }
            if (! Schema::hasColumn('saml_configurations', 'attribute_release')) {
                $table->json('attribute_release')->nullable()->after('default_relay_state');
            }
            if (! Schema::hasColumn('saml_configurations', 'require_signed_requests')) {
                $table->boolean('require_signed_requests')->default(false)->after('attribute_release');
            }
            if (! Schema::hasColumn('saml_configurations', 'sign_responses')) {
                $table->boolean('sign_responses')->default(true)->after('require_signed_requests');
            }
            if (! Schema::hasColumn('saml_configurations', 'sign_assertions')) {
                $table->boolean('sign_assertions')->default(false)->after('sign_responses');
            }
            if (! Schema::hasColumn('saml_configurations', 'notes')) {
                $table->text('notes')->nullable()->after('metadata_xml');
            }
        });

        DB::table('saml_configurations')->orderBy('id')->get()->each(function ($configuration) {
            $base = Str::slug($configuration->name ?: 'saml-provider') ?: 'saml-provider';
            $slug = $base;
            $suffix = 2;
            while (DB::table('saml_configurations')->where('slug', $slug)->where('id', '!=', $configuration->id)->exists()) {
                $slug = $base.'-'.$suffix++;
            }

            DB::table('saml_configurations')->where('id', $configuration->id)->update([
                'slug' => $configuration->slug ?: $slug,
                'mode' => $configuration->mode ?: 'idp',
                'signing_algo' => $configuration->signing_algo ?: 'rsa-sha256',
                'attribute_release' => $configuration->attribute_release ?: json_encode([
                    'email', 'first_name', 'last_name', 'display_name', 'role', 'department',
                ]),
                'require_signed_requests' => (bool) ($configuration->require_signed_requests ?? false),
                'sign_responses' => true,
                // OnePortal signs its Response element, not the Assertion.
                'sign_assertions' => false,
            ]);
        });

        Schema::table('saml_configurations', function (Blueprint $table) {
            $table->unique('slug');
            $table->index(['mode', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('saml_configurations', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['mode', 'is_active']);
            $table->dropColumn([
                'slug', 'mode', 'acs_url', 'signing_algo', 'attribute_release',
                'require_signed_requests', 'sign_responses', 'sign_assertions', 'notes',
            ]);
        });
    }
};
