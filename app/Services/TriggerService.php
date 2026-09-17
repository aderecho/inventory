<?php

namespace App\Services;

use App\Models\Trigger;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TriggerService
{
    private string $employeeApiUrl;
    public function __construct()
    {
        $this->employeeApiUrl = config('services.puso_api.base_url');
    }

    public function sync(): Trigger
    {
        return DB::transaction(function () {
            $page = 1;
            $lastPage = 1;

            do {
                $response = Http::timeout(30)
                    ->get($this->employeeApiUrl, [
                        'page' => $page,
                    ]);

                if ($response->failed()) {
                    throw new RuntimeException(
                        "Employee API failed on page {$page}. HTTP status: {$response->status()}"
                    );
                }

                $data = $response->json();

                $employees = $data['_data'] ?? [];
                $metadata = $data['metadata'] ?? [];

                $lastPage = $metadata['last_page'] ?? 1;

                foreach ($employees as $employee) {
                    $this->syncEmployee($employee);
                }

                $page++;
            } while ($page <= $lastPage);

            return Trigger::create([
                'date' => now()->toDateString(),
                'status' => 1,
            ]);
        });
    }

    private function syncEmployee(array $employee): void
    {
        $email = trim($employee['up_mail'] ?? '');

        // Skip employee if email is invalid
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $status = strtolower($employee['status'] ?? '') === 'active'
            ? 1
            : 0;

        /*
     * 1. Find existing user by email.
     *
     * If the email already exists, we KEEP the existing users.id.
     * If it doesn't exist, create a new user.
     */
        $user = User::where('email', $email)->first();

        if (!$user) {
            $user = User::create([
                'email' => $email,
                'status' => $status,
            ]);
        } else {
            $user->update([
                'status' => $status,
            ]);
        }

        /*
     * 2. Find the profile using the user's ID.
     *
     * user_profiles.id must always be the same as users.id.
     */
        $profile = UserProfile::find($user->id);

        if (!$profile) {
            $profile = new UserProfile();

            // Force profile ID to match users.id
            $profile->id = $user->id;
        }

        /*
     * 3. Update the employee profile information.
     */
        $profile->fill([
            'user_id' => $user->id,
            'employee_number' => $employee['employee_number'] ?? null,

            'title_name' => $employee['title_name'] ?? null,

            'first_name' => $employee['first_name'] ?? '',

            'middle_name' => $employee['middle_name'] ?? null,

            'last_name' => $employee['last_name'] ?? '',

            'ext_name' => !empty($employee['ext_name'])
                ? $employee['ext_name']
                : null,

            'primary_unit_division_department' =>
            $employee['primary_unit_division_department'] ?? null,

            'employee_primary_unit_college' =>
            $employee['employee_primary_unit_college'] ?? null,
        ]);

        $profile->save();
    }
}
