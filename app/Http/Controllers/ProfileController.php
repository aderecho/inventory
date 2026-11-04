<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\PropertyNumberPreferenceRequest;
use App\Services\ProfileService;

class ProfileController extends Controller
{
    public function __construct(protected ProfileService $profileService) {}

    public function update(ProfileUpdateRequest $request)
    {
        $this->profileService->updateOwnProfile($request->user(), $request->validated());

        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    public function updatePropertyNumberPreference(PropertyNumberPreferenceRequest $request)
    {
        $request->user()->update([
            'free_text_property_number' => $request->boolean('free_text_property_number'),
        ]);

        return response()->json([
            'free_text_property_number' => $request->user()->free_text_property_number,
        ]);
    }
}
