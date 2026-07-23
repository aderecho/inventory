<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('saml_configurations', 'metadata_xml')) {
            Schema::table('saml_configurations', function (Blueprint $table) {
                $table->longText('metadata_xml')->nullable()->after('x509_cert');
            });
        }

        if (! Schema::hasColumn('saml_configurations', 'status')) {
            Schema::table('saml_configurations', function (Blueprint $table) {
                $table->string('status')->default('active')->after('default_relay_state');
                $table->index('status');
            });
        }

        DB::table('saml_configurations')
            ->whereNull('status')
            ->orWhereNotIn('status', ['active', 'inactive'])
            ->update(['status' => 'active']);
    }

    public function down(): void
    {
        if (Schema::hasColumn('saml_configurations', 'status')) {
            Schema::table('saml_configurations', function (Blueprint $table) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            });
        }

        if (Schema::hasColumn('saml_configurations', 'metadata_xml')) {
            Schema::table('saml_configurations', function (Blueprint $table) {
                $table->dropColumn('metadata_xml');
            });
        }
    }
};
