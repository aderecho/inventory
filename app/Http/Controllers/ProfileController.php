<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\ProfileService;

class ProfileController extends Controller
{
    public function __construct(protected ProfileService $profileService) {}

    public function update(ProfileUpdateRequest $request)
    {
        $this->profileService->updateOwnProfile($request->user(), $request->validated());

        return redirect()->back()->with('success', 'Profile updated successfully');
    }
}