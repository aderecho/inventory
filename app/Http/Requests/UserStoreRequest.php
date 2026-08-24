<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // adjust authorization as needed
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? null;

        return [
            'email' => [
                'required',
                'email',
                'ends_with:@up.edu.ph',
                'unique:users,email,' . $userId,
            ],

            'status' => 'required|integer|in:0,1',

            'user_profiles.first_name' => 'required|string|max:255',
            'user_profiles.last_name' => 'required|string|max:255',
            'user_profiles.middle_name' => 'nullable|string|max:255',
            'user_profiles.contact_number' => 'nullable|string|max:50',

            'role' => 'required|string|exists:roles,name',

            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',

            'organizations' => 'required|array|min:1',
            'organizations.*' => 'integer|exists:organizations,id',

            'primary_organization_id' => [
                'required',
                'integer',
                'exists:organizations,id',
                Rule::in($this->input('organizations', [])),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'The email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.ends_with' => 'The email address must use an @up.edu.ph email address.',
            'email.unique' => 'This email address is already registered.',

            'status.required' => 'The status field is required.',

            'user_profiles.first_name.required' => 'The first name field is required.',
            'user_profiles.last_name.required' => 'The last name field is required.',

            'role.required' => 'The role field is required.',

            'organizations.required' => 'Please select at least one unit.',
            'organizations.min' => 'Please select at least one unit.',
            'organizations.*.exists' => 'One or more selected units are invalid.',

            'primary_organization_id.required' => 'Please select a primary unit.',
            'primary_organization_id.in' => 'The primary unit must be one of the selected units.',
        ];
    }
}