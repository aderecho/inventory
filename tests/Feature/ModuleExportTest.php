<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\RoomApiService;
use App\Services\RoomExportService;
use App\Services\SupplierExportService;
use App\Services\UserExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModuleExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_supplier_export_includes_the_id_column(): void
    {
        $supplier = Supplier::factory()->create([
            'supplier_name' => 'Northwind Supply',
            'contact_no' => '0917 000 0001',
            'email' => 'northwind@example.com',
            'address' => '1 Sample Street',
            'status' => 1,
        ]);

        $rows = $this->csvRows(app(SupplierExportService::class)->exportToFile('csv'));

        $this->assertSame('ID', $rows[0][0]);
        $this->assertSame('Supplier Name', $rows[0][1]);
        $this->assertSame((string) $supplier->id, $rows[1][0]);
        $this->assertSame('Northwind Supply', $rows[1][1]);
    }

    public function test_the_user_export_includes_the_id_column(): void
    {
        $user = User::factory()->withProfile()->create([
            'email' => 'export.user@example.com',
            'status' => 1,
        ]);

        $user->userProfiles->update([
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Cruz',
        ]);

        $rows = $this->csvRows(app(UserExportService::class)->exportToFile('csv'));
        $row = $rows[1];

        $this->assertSame(['ID', 'Full Name', 'First Name', 'Middle Name', 'Last Name'], $rows[0]);
        $this->assertSame((string) $user->id, $row[0]);
        $this->assertSame('Maria Santos Cruz', $row[1]);
        $this->assertSame('Maria', $row[2]);
        $this->assertSame('Santos', $row[3]);
        $this->assertSame('Cruz', $row[4]);
    }

    public function test_the_room_export_includes_the_api_id_column(): void
    {
        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn([
                'success' => true,
                'data' => [[
                    'id' => 7,
                    'room_name' => 'ADM 201',
                    'room_code' => 'ADM201',
                    'description' => 'Administration office',
                    'building_name' => 'Main Building',
                ]],
            ]);
        });

        $rows = $this->csvRows(app(RoomExportService::class)->exportToFile('csv'));

        $this->assertSame('ID', $rows[0][0]);
        $this->assertSame('7', $rows[1][0]);
        $this->assertSame('ADM 201', $rows[1][1]);
        $this->assertSame(['ID', 'Room Name', 'Description', 'Building Name'], $rows[0]);
    }

    public function test_user_management_can_download_users_and_rooms(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(Permission::findOrCreate('view users', 'web'));

        $this->mock(RoomApiService::class, function ($mock) {
            $mock->shouldReceive('fetchRooms')->andReturn([
                'success' => true,
                'data' => [['id' => 7, 'room_name' => 'ADM 201']],
            ]);
        });

        $this->actingAs($user)
            ->get(route('user_management.export', ['format' => 'csv']))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('user_management.export-rooms', ['format' => 'csv']))
            ->assertOk();
    }

    /** @return array<int, array<int, string|null>> */
    private function csvRows(string $path): array
    {
        $handle = fopen($path, 'rb');
        $rows = [];

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $rows[] = $row;
        }

        fclose($handle);
        $rows[0][0] = preg_replace('/^\xEF\xBB\xBF/', '', $rows[0][0]);

        return $rows;
    }
}
