<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('acknowledgement_receipts', function (Blueprint $table) {
            $table->increments('id');
            $table->date('par_date');
            $table->unsignedInteger('issued_by_id');
            $table->string('category', 20)->nullable();
            $table->string('remarks', 255)->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->foreign('issued_by_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acknowledgement_receipts');
    }
};
