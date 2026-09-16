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

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $status = strtolower($employee['status'] ?? '') === 'active'
            ? 1
            : 0;

        $profile = UserProfile::where('employee_number', $employee['employee_number'])
            ->first();

        if ($profile) {
            $user = $profile->user;
            $user->update([
                'email' => $email,
                'status' => $status,
            ]);
        } else {
            $user = User::where('email', $email)->first();

            if ($user) {
                $user->update([
                    'status' => $status,
                ]);
            } else {
                $user = User::create([
                    'email' => $email,
                    'status' => $status,
                ]);
            }
        }

        UserProfile::updateOrCreate(
            [
                'employee_number' => $employee['employee_number'],
            ],
            [
                'user_id' => $user->id,

                'title_name' =>
                $employee['title_name'] ?? null,

                'first_name' =>
                $employee['first_name'] ?? '',

                'middle_name' =>
                $employee['middle_name'] ?? null,

                'last_name' =>
                $employee['last_name'] ?? '',

                'ext_name' =>
                !empty($employee['ext_name'])
                    ? $employee['ext_name']
                    : null,

                'primary_unit_division_department' =>
                $employee['primary_unit_division_department'] ?? null,

                'employee_primary_unit_college' =>
                $employee['employee_primary_unit_college'] ?? null,

                'contact_number' => null,
            ]
        );
    }
}
