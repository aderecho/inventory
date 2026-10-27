<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Borrowing: one ticket per borrow event (item_borrowings) with one row per
     * item (item_borrowing_items), so several items can be lent under a single
     * reference code and returned one at a time.
     */
    public function up(): void
    {
        Schema::create('item_borrowings', function (Blueprint $table) {
            $table->id();

            $table->string('reference_code', 32)->unique();

            // staff | student | outsider
            $table->string('borrower_type', 20);
            $table->string('borrower_name');

            // Staff link, kept alongside the name so history survives edits.
            $table->unsignedInteger('borrower_user_id')->nullable();
            $table->string('employee_number', 50)->nullable();

            $table->string('student_number', 50)->nullable();
            $table->string('organization', 255)->nullable();
            $table->string('contact_number', 50)->nullable();

            $table->text('notes')->nullable();

            $table->timestamp('borrowed_at');
            // Null due date means "no fixed return date" - never overdue.
            $table->timestamp('due_at')->nullable();
            $table->timestamp('returned_at')->nullable();

            // borrowed | returned | cancelled
            $table->string('status', 20)->default('borrowed');

            $table->unsignedInteger('released_by')->nullable();
            $table->unsignedInteger('received_by')->nullable();

            $table->timestamps();

            $table->index(['status', 'due_at']);
            $table->index('borrower_type');

            $table->foreign('borrower_user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('released_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('item_borrowing_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_borrowing_id')
                ->constrained('item_borrowings')
                ->cascadeOnDelete();

            $table->unsignedBigInteger('inventory_item_id');
            $table->unsignedInteger('quantity')->default(1);

            // Condition when it left and when it came back.
            $table->unsignedBigInteger('condition_out_id')->nullable();
            $table->unsignedBigInteger('condition_in_id')->nullable();

            $table->timestamp('returned_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
            $table->foreign('condition_out_id')->references('id')->on('asset_conditions')->nullOnDelete();
            $table->foreign('condition_in_id')->references('id')->on('asset_conditions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_borrowing_items');
        Schema::dropIfExists('item_borrowings');
    }
};

