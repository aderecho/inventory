<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id ?? null;

        return [
            'email' => 'required|email|unique:users,email,' . $userId,
            'password' => 'nullable|string|min:8',
            'status' => 'required|integer|in:0,1',
            'user_profiles.first_name' => 'required|string|max:255',
            'user_profiles.last_name'  => 'required|string|max:255',
            'user_profiles.middle_name' => 'required|string|max:255',
            'user_profiles.contact_number' => 'required|string|max:50',
            'role' => 'required|string|exists:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'The password must be at least 8 characters.',
            'status.required' => 'The status field is required.',
            'user_profiles.first_name.required' => 'The first name field is required.',
            'user_profiles.last_name.required'  => 'The last name field is required.',
            'user_profiles.middle_name.required' => 'The middle name field is required.',
            'user_profiles.contact_number.required' => 'The contact number field is required.',
            'role.required' => 'The role field is required.',
        ];
    }
}
