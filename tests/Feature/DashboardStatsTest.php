<?php

namespace Tests\Feature;

use App\Models\Supplier;
use App\Models\User;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    private const SPMO = 'UPC SUPPLY AND PROPERTY MANAGEMENT OFFICE';

    public function test_only_profiles_in_the_spmo_department_count_as_spmo_users(): void
    {
        $this->userInDepartment(self::SPMO);
        $this->userInDepartment('UPC INFORMATION TECHNOLOGY CENTER');

        $stats = app(DashboardService::class)->getStats();

        $this->assertSame(2, $stats['users']);
        $this->assertSame(1, $stats['users_spmo']);
    }

    public function test_the_user_search_matches_the_spmo_department(): void
    {
        // The dashboard links to user management by searching this department,
        // so the search scope has to reach that column.
        $spmo = $this->userInDepartment(self::SPMO);
        $this->userInDepartment('UPC INFORMATION TECHNOLOGY CENTER');

        $this->assertSame([$spmo->id], User::search(self::SPMO)->pluck('id')->all());
    }

    public function test_archived_suppliers_sit_outside_the_active_total(): void
    {
        Supplier::factory()->create();
        Supplier::factory()->create()->delete();

        $stats = app(DashboardService::class)->getStats();

        $this->assertSame(1, $stats['suppliers']);
        $this->assertSame(1, $stats['suppliers_archived']);
    }

    private function userInDepartment(string $department): User
    {
        $user = User::factory()->create();

        $user->userProfiles()->create([
            'first_name' => 'Test',
            'middle_name' => 'Middle',
            'last_name' => 'User',
            'contact_number' => '09170000000',
            'primary_unit_division_department' => $department,
        ]);

        return $user;
    }
}
