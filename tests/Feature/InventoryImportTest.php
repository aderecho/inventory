<?php

namespace Tests\Feature;

use App\Models\AssetCondition;
use App\Models\AcknowledgementReceipt;
use App\Models\InventoryItem;
use App\Models\ItemClassification;
use App\Models\Supplier;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\InventoryImportService;
use App\Services\RoomApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class InventoryImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_imports_the_new_batch_add_columns_and_accepts_blank_optional_cells(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn([
                'data' => [['id' => 7, 'room_name' => 'Supply Office']],
            ]);
        });

        $user = User::factory()->create();
        $this->actingAs($user);

        $profile = UserProfile::factory()->create(['user_id' => $user->id]);
        $supplier = Supplier::factory()->create();
        ItemClassification::create([
            'classification_code' => '221',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);
        AssetCondition::create([
            'condition_name' => 'Good',
            'disposition' => AssetCondition::DISPOSITION_SERVICEABLE,
        ]);

        $file = UploadedFile::fake()->createWithContent('items.csv', $this->csv([
            ['221-2024-03-512', '', '221-905-001', 'OLD-221-001', 1, 'unit', 100, 100, 'Chair', 'Brand', 'Model',
                'A chair', 'SN-1', $profile->id, '164', '', '', 7, $supplier->id, 'INV-1', 'Office chair', $user->id],
            ['', '', '221-905-002', '', 1, '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', '', ''],
        ]));

        $result = app(InventoryImportService::class)->import($file);

        $this->assertSame(2, $result['imported_rows']);
        $this->assertSame(2, $result['imported_items']);
        $this->assertSame([], $result['failed']);

        $assigned = InventoryItem::where('property_number', '221-905-001')->firstOrFail();
        $this->assertSame($supplier->id, $assigned->supplier_id);
        $this->assertSame('OLD-221-001', $assigned->old_property_number);
        $this->assertSame(7, $assigned->latestHistoryLocation?->room_id);
        $this->assertSame($profile->id, $assigned->latestAcknowledgementItem?->accountable_person_id);
        $this->assertSame($user->id, $assigned->latestAcknowledgementItem?->issued_by_id);
        $this->assertSame('221-2024-03-512', $assigned->latestAcknowledgementItem?->acknowledgementReceipts?->category);
        $this->assertNull($assigned->latestAcknowledgementItem?->acknowledgementReceipts?->par_date);

        $blank = InventoryItem::where('property_number', '221-905-002')->firstOrFail();
        $this->assertNull($blank->supplier_id);
        $this->assertNull($blank->pr_number);
        $this->assertNull($blank->po_number);
        $this->assertNull($blank->unit);
        $this->assertNull($blank->unit_cost);
        $this->assertNull($blank->total_amount);
        $this->assertNull($blank->latestHistoryLocation);
        $this->assertNull($blank->latestAcknowledgementItem);
    }

    public function test_multiple_items_can_share_the_same_par_ics_number(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $user = User::factory()->create();
        $this->actingAs($user);
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);

        ItemClassification::create([
            'classification_code' => '221',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);
        AssetCondition::create([
            'condition_name' => 'Good',
            'disposition' => AssetCondition::DISPOSITION_SERVICEABLE,
        ]);

        $first = array_fill(0, 22, '');
        $first[0] = 'PAR-2026-001';
        $first[2] = '221-905-001';
        $first[4] = 1;
        $first[13] = $profile->id;
        $first[21] = $user->id;

        $second = $first;
        $second[2] = '221-905-002';

        $result = app(InventoryImportService::class)->import(
            UploadedFile::fake()->createWithContent('items.csv', $this->csv([$first, $second]))
        );

        $this->assertSame(2, $result['imported_rows']);
        $this->assertSame(1, $result['receipts_created']);
        $this->assertSame(1, AcknowledgementReceipt::count());

        $receiptId = AcknowledgementReceipt::firstOrFail()->id;
        $this->assertSame(
            $receiptId,
            InventoryItem::where('property_number', '221-905-001')->firstOrFail()->latestAcknowledgementItem?->acknowledgement_id,
        );
        $this->assertSame(
            $receiptId,
            InventoryItem::where('property_number', '221-905-002')->firstOrFail()->latestAcknowledgementItem?->acknowledgement_id,
        );
    }

    public function test_a_year_first_property_number_uses_the_par_prefix_for_classification(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $user = User::factory()->create();
        $this->actingAs($user);
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);
        $classification = ItemClassification::create([
            'classification_code' => '222',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);
        AssetCondition::create([
            'condition_name' => 'Good',
            'disposition' => AssetCondition::DISPOSITION_SERVICEABLE,
        ]);

        $row = array_fill(0, 22, '');
        $row[0] = '222-2022-06-268';
        $row[2] = '2022-70-10-1066-123';
        $row[4] = 1;
        $row[13] = $profile->id;
        $row[21] = $user->id;

        $result = app(InventoryImportService::class)->import(
            UploadedFile::fake()->createWithContent('items.csv', $this->csv([$row]))
        );

        $this->assertSame(1, $result['imported_rows']);
        $this->assertSame([], $result['failed']);
        $this->assertSame(
            $classification->id,
            InventoryItem::where('property_number', '2022-70-10-1066-123')->firstOrFail()->item_classification_id,
        );
    }

    public function test_it_rejects_a_property_number_longer_than_19_characters(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $user = User::factory()->create();
        $this->actingAs($user);
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);
        ItemClassification::create([
            'classification_code' => '222',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);
        AssetCondition::create([
            'condition_name' => 'Good',
            'disposition' => AssetCondition::DISPOSITION_SERVICEABLE,
        ]);

        $row = array_fill(0, 22, '');
        $row[0] = '222-2022-06-268';
        $row[2] = '2022-70-10-1066-1234';
        $row[4] = 1;
        $row[13] = $profile->id;
        $row[21] = $user->id;

        $result = app(InventoryImportService::class)->import(
            UploadedFile::fake()->createWithContent('items.csv', $this->csv([$row]))
        );

        $this->assertSame(0, $result['imported_rows']);
        $this->assertStringContainsString('longer than 19 characters', $result['failed'][0]['messages'][0]);
    }

    public function test_a_blank_item_name_is_not_filled_from_the_description(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $user = User::factory()->create();
        $this->actingAs($user);
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);
        ItemClassification::create([
            'classification_code' => '222',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);
        AssetCondition::create([
            'condition_name' => 'Good',
            'disposition' => AssetCondition::DISPOSITION_SERVICEABLE,
        ]);

        $row = array_fill(0, 22, '');
        $row[0] = '222-2022-06-268';
        $row[2] = '2022-70-10-1066-123';
        $row[4] = 1;
        $row[11] = 'Description only, item name is blank';
        $row[13] = $profile->id;
        $row[21] = $user->id;

        $result = app(InventoryImportService::class)->import(
            UploadedFile::fake()->createWithContent('items.csv', $this->csv([$row]))
        );

        $this->assertSame(1, $result['imported_rows']);

        $item = InventoryItem::where('property_number', '2022-70-10-1066-123')->firstOrFail();
        $this->assertNull($item->item_name);
        $this->assertSame('Description only, item name is blank', $item->description);
    }

    public function test_an_unmatched_classification_prefix_does_not_block_the_import(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $user = User::factory()->create();
        $this->actingAs($user);
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);
        ItemClassification::create([
            'classification_code' => '222',
            'classification_name' => 'Office Equipment',
            'status' => 1,
        ]);
        AssetCondition::create([
            'condition_name' => 'Good',
            'disposition' => AssetCondition::DISPOSITION_SERVICEABLE,
        ]);

        $row = array_fill(0, 22, '');
        $row[0] = '999-2026-001';
        $row[2] = '2026-70-10-1066-123';
        $row[4] = 1;
        $row[13] = $profile->id;
        $row[21] = $user->id;

        $result = app(InventoryImportService::class)->import(
            UploadedFile::fake()->createWithContent('items.csv', $this->csv([$row]))
        );

        $this->assertSame(1, $result['imported_rows']);
        $this->assertSame([], $result['failed']);
        $this->assertNull(
            InventoryItem::where('property_number', '2026-70-10-1066-123')->firstOrFail()->item_classification_id,
        );
    }

    public function test_it_imports_only_the_active_sheet(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $spreadsheet = new Spreadsheet();

        $first = $spreadsheet->getActiveSheet();
        $first->setTitle('First');
        $first->fromArray(['Property number', 'qty'], null, 'A1');
        $first->fromArray(['111-000-001', 1], null, 'A2');

        $active = $spreadsheet->createSheet();
        $active->setTitle('Active');
        $active->fromArray(['Property number', 'qty'], null, 'A1');
        $active->fromArray(['2022-70-10-1066-123', 1], null, 'A2');
        $spreadsheet->setActiveSheetIndex(1);

        $path = tempnam(sys_get_temp_dir(), 'import_active_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $result = app(InventoryImportService::class)->import(
            new UploadedFile(
                $path,
                'items.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            )
        );

        $this->assertSame('Active', $result['sheet']);
        $this->assertDatabaseHas('inventory_items', [
            'property_number' => '2022-70-10-1066-123',
        ]);
        $this->assertDatabaseMissing('inventory_items', [
            'property_number' => '111-000-001',
        ]);

        unlink($path);
    }

    public function test_it_lists_sheet_names_and_can_import_a_chosen_sheet(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $spreadsheet = new Spreadsheet();

        $first = $spreadsheet->getActiveSheet();
        $first->setTitle('First');
        $first->fromArray(['Property number', 'qty'], null, 'A1');
        $first->fromArray(['111-000-001', 1], null, 'A2');

        $active = $spreadsheet->createSheet();
        $active->setTitle('Active');
        $active->fromArray(['Property number', 'qty'], null, 'A1');
        $active->fromArray(['2022-70-10-1066-123', 1], null, 'A2');
        $spreadsheet->setActiveSheetIndex(1);

        $path = tempnam(sys_get_temp_dir(), 'import_choice_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $file = new UploadedFile(
            $path,
            'items.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true,
        );
        $service = app(InventoryImportService::class);

        $this->assertSame([
            ['name' => 'First', 'active' => false],
            ['name' => 'Active', 'active' => true],
        ], $service->sheetNames($file));

        $result = $service->import($file, sheetName: 'First');

        $this->assertSame('First', $result['sheet']);
        $this->assertDatabaseHas('inventory_items', [
            'property_number' => '111-000-001',
        ]);
        $this->assertDatabaseMissing('inventory_items', [
            'property_number' => '2022-70-10-1066-123',
        ]);

        unlink($path);
    }

    public function test_item_condition_import_is_case_insensitive(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $user = User::factory()->create();
        $this->actingAs($user);
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);
        $condition = AssetCondition::create([
            'condition_name' => 'Disposed',
            'disposition' => AssetCondition::DISPOSITION_DISPOSED,
        ]);

        $row = array_fill(0, 23, '');
        $row[0] = '222-2026-001';
        $row[2] = '222-2026-001-234';
        $row[4] = 1;
        $row[13] = $profile->id;
        $row[21] = $user->id;
        $row[22] = 'DiSpOSED';

        $result = app(InventoryImportService::class)->import(
            UploadedFile::fake()->createWithContent('items.csv', $this->csvWithCondition([$row]))
        );

        $this->assertSame(1, $result['imported_rows']);
        $this->assertSame(1, $result['disposal_items']);

        $item = InventoryItem::where('property_number', '222-2026-001-234')->firstOrFail();
        $this->assertSame(InventoryItem::STATUS_DISPOSED, $item->status);
        $this->assertDatabaseHas('asset_inspections', [
            'inventory_item_id' => $item->id,
            'asset_condition_id' => $condition->id,
        ]);
    }

    public function test_par_date_accepts_mm_dd_yyyy(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $user = User::factory()->create();
        $this->actingAs($user);
        $profile = UserProfile::factory()->create(['user_id' => $user->id]);

        $row = array_fill(0, 22, '');
        $row[0] = '222-2026-001';
        $row[1] = '11-10-2026';
        $row[2] = '222-2026-002-234';
        $row[4] = 1;
        $row[13] = $profile->id;
        $row[21] = $user->id;

        $result = app(InventoryImportService::class)->import(
            UploadedFile::fake()->createWithContent('items.csv', $this->csv([$row]))
        );

        $this->assertSame(1, $result['imported_rows']);
        $this->assertSame('2026-11-10', AcknowledgementReceipt::firstOrFail()->par_date);
    }

    public function test_date_acquired_is_imported_from_the_template(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Property No.', 'Qty', 'Date Acquired'], null, 'A1');
        $sheet->fromArray(['2022-70-10-1066-123', 1, '11-10-2026'], null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'import_date_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $result = app(InventoryImportService::class)->import(
            new UploadedFile(
                $path,
                'items.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            )
        );

        $this->assertSame(1, $result['imported_rows']);
        $this->assertSame(
            '2026-11-10',
            InventoryItem::where('property_number', '2022-70-10-1066-123')->firstOrFail()->date_acquired,
        );

        unlink($path);
    }

    public function test_new_old_property_numbers_and_serial_are_imported_separately(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn(['data' => []]);
        });

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['New Property No.', 'Old Property No.', 'Serial No.', 'Qty'], null, 'A1');
        $sheet->fromArray(['2026-50-30-2000-234', '2016-70-10-1066-123', 'SERIAL-001', 1], null, 'A2');

        $path = tempnam(sys_get_temp_dir(), 'import_property_pair_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);

        $result = app(InventoryImportService::class)->import(
            new UploadedFile(
                $path,
                'items.xlsx',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                null,
                true,
            )
        );

        $this->assertSame(1, $result['imported_rows']);
        $item = InventoryItem::where('property_number', '2026-50-30-2000-234')->firstOrFail();
        $this->assertSame('2016-70-10-1066-123', $item->old_property_number);
        $this->assertSame('SERIAL-001', $item->serial_number);

        unlink($path);
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function csv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'PAR/ICS number', 'PAR/ICS date', 'Property number', 'old property number', 'qty', 'unit', 'unit cost',
            'total amount', 'item name', 'brand', 'model', 'description', 'serial number',
            'accountable person', 'fund source', 'pr number', 'po number', 'location',
            'supplier', 'invoice', 'remarks', 'issued by',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /** @param array<int, array<int, mixed>> $rows */
    private function csvWithCondition(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'PAR/ICS number', 'PAR/ICS date', 'Property number', 'old property number', 'qty', 'unit', 'unit cost',
            'total amount', 'item name', 'brand', 'model', 'description', 'serial number',
            'accountable person', 'fund source', 'pr number', 'po number', 'location',
            'supplier', 'invoice', 'remarks', 'issued by', 'item condition',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
