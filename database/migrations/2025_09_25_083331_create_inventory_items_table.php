<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('item_classification_id');
            $table->unsignedInteger('supplier_id');
            $table->string('invoice', 50)->nullable();
            $table->string('fund_source', 50)->nullable();
            $table->string('item_name', 255)->nullable();
            $table->string('description', 255)->nullable();
            $table->integer('quantity')->nullable();
            $table->string('unit', 40)->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('total_amount', 14, 2)->nullable();
            $table->string('property_number', 80)->unique();
            $table->string('serial_number', 80)->nullable();
            $table->string('pr_number', 60);
            $table->string('po_number', 60);
            $table->string('remarks', 255)->nullable();
            $table->date('date_acquired')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();

            $table->foreign('item_classification_id')->references('id')->on('item_classifications')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
