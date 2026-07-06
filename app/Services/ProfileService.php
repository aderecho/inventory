<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function updateOwnProfile(User $user, array $data): User
    {
        $updateData = [
            'email' => $data['email'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            $data['user_profiles'] ?? []
        );

        return $user;
    }
}