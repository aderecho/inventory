<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Any authenticated user may update their own profile.
        return true;
    }

    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],

            'current_password' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', Password::min(8)],

            'user_profiles.first_name' => ['required', 'string', 'max:255'],
            'user_profiles.last_name' => ['required', 'string', 'max:255'],
            'user_profiles.middle_name' => ['nullable', 'string', 'max:255'],
            'user_profiles.contact_number' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required_with' => 'Please enter your current password to set a new one.',
            'current_password.current_password' => 'The current password you entered is incorrect.',
            'user_profiles.first_name.required' => 'The first name field is required.',
            'user_profiles.last_name.required' => 'The last name field is required.',
        ];
    }
}
