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
        Schema::create('asset_inspections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_item_id')
                ->constrained('inventory_items')
                ->cascadeOnDelete();

            $table->foreignId('asset_condition_id')
                ->constrained('asset_conditions')
                ->restrictOnDelete();

            $table->unsignedInteger('inspected_by')->nullable();

            $table->foreign('inspected_by')
                ->references('id')
                ->on('users')
                ->nullOnDelete();

            $table->date('inspection_date');

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_inspections');
    }
};
