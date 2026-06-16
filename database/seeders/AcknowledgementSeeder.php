<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AccountablePerson;
use App\Models\AcknowledgementReceipt;
use App\Models\AcknowledgementItem;

class AcknowledgementSeeder extends Seeder
{
    public function run(): void
    {
        // Base data (must already exist)
        $users = User::all();

        // Safety checks (avoid empty-table crashes)
        if ($users->isEmpty()) {
            $this->command?->warn('AcknowledgementSeeder skipped: missing users.');
            return;
        }

        // Acknowledgement Receipts
        $ackReceipts = AcknowledgementReceipt::factory(30)->create([
            'issued_by_id'           => fn () => $users->random()->id,
            'created_by'             => fn () => $users->random()->id,
        ]);

        // Acknowledgement Items
        AcknowledgementItem::factory(10)->create([
            'acknowledgement_id' => fn () => $ackReceipts->random()->id,
        ]);
    }
}
