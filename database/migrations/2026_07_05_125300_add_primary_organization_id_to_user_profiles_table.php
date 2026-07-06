<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('primary_organization_id')->nullable()->after('user_id');

            $table->foreign('primary_organization_id')
                ->references('id')->on('organizations')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('user_profiles', function (Blueprint $table) {
            $table->dropForeign(['primary_organization_id']);
            $table->dropColumn('primary_organization_id');
        });
    }
};