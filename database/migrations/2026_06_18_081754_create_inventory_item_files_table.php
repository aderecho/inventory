<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_item_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('acknowledgement_item_id');
            $table->uuid('file_group_id');
            $table->string('file_path');
            $table->string('file_type');
            $table->unsignedInteger('upload_by');
            $table->timestamps();

            $table->foreign('acknowledgement_item_id')->references('id')->on('acknowledgement_items')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('upload_by')->references('id')->on('user_profiles')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_item_files');
    }
};
