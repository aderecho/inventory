<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('acknowledgement_item_transfer_type', function (Blueprint $table) {
            $table->unsignedInteger('acknowledgement_item_id');
            $table->unsignedBigInteger('transfer_type_id');
            $table->timestamps();

            $table->unique(
                ['acknowledgement_item_id', 'transfer_type_id'],
                'ack_item_transfer_type_unique'
            );

            $table->foreign('acknowledgement_item_id', 'ack_item_transfer_ack_item_foreign')
                ->references('id')->on('acknowledgement_items')
                ->cascadeOnDelete()->cascadeOnUpdate();

            $table->foreign('transfer_type_id', 'ack_item_transfer_transfer_type_foreign')
                ->references('id')->on('transfer_types')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });

        $this->backfillFromReceipts();
    }

    /**
     * Move any transfer types previously saved on the receipt down to its items.
     */
    protected function backfillFromReceipts(): void
    {
        if (! Schema::hasColumn('acknowledgement_receipts', 'transfer_type')) {
            return;
        }

        $receipts = DB::table('acknowledgement_receipts')
            ->whereNotNull('transfer_type')
            ->get(['id', 'transfer_type']);

        foreach ($receipts as $receipt) {
            $typeIds = array_values(array_unique(array_filter(
                (array) json_decode((string) $receipt->transfer_type, true),
                fn ($id) => $id !== null && $id !== ''
            )));

            if (empty($typeIds)) {
                continue;
            }

            $itemIds = DB::table('acknowledgement_items')
                ->where('acknowledgement_id', $receipt->id)
                ->pluck('id');

            $rows = [];

            foreach ($itemIds as $itemId) {
                foreach ($typeIds as $typeId) {
                    $rows[] = [
                        'acknowledgement_item_id' => $itemId,
                        'transfer_type_id'        => $typeId,
                        'created_at'              => now(),
                        'updated_at'              => now(),
                    ];
                }
            }

            if (! empty($rows)) {
                DB::table('acknowledgement_item_transfer_type')->insertOrIgnore($rows);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acknowledgement_item_transfer_type');
    }
};
