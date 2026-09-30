<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'email' => [
                'required',
                'email',
                'ends_with:@up.edu.ph',
                Rule::unique('users', 'email')->ignore($userId),
            ],

            'status' => 'required|integer|in:0,1',

            'user_profiles.first_name' => 'required|string|max:255',
            'user_profiles.last_name' => 'required|string|max:255',
            'user_profiles.middle_name' => 'nullable|string|max:255',
            'user_profiles.contact_number' => 'nullable|string|max:50',
            'user_profiles.employee_number' => 'nullable|string|max:255',
            'user_profiles.title_name' => 'nullable|string|max:255',
            'user_profiles.ext_name' => 'nullable|string|max:255',
            'user_profiles.primary_unit_division_department' => 'nullable|string|max:255',
            'user_profiles.employee_primary_unit_college' => 'nullable|string|max:255',

            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],

            'permissions' => 'nullable|array',
            'permissions.*' => 'string|exists:permissions,name',
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
            'status.in' => 'The selected status is invalid.',

            'user_profiles.first_name.required' => 'The first name field is required.',
            'user_profiles.last_name.required' => 'The last name field is required.',
            'user_profiles.middle_name.string' => 'The middle name must be a valid text value.',
            'user_profiles.contact_number.string' => 'The contact number must be a valid text value.',
            'user_profiles.employee_number.string' => 'The employee number must be a valid text value.',
            'user_profiles.title_name.string' => 'The title must be a valid text value.',
            'user_profiles.ext_name.string' => 'The suffix must be a valid text value.',
            'user_profiles.primary_unit_division_department.string' => 'The department must be a valid text value.',
            'user_profiles.employee_primary_unit_college.string' => 'The college must be a valid text value.',

            'roles.required' => 'At least one role is required.',
            'roles.array' => 'The roles value must be a list.',
            'roles.min' => 'At least one role must be selected.',
            'roles.*.string' => 'Each role must be a valid text value.',
            'roles.*.exists' => 'One or more selected roles do not exist.',

            'permissions.array' => 'The permissions value must be a list.',
            'permissions.*.string' => 'Each permission must be a valid text value.',
            'permissions.*.exists' => 'One or more selected permissions are invalid.',
        ];
    }
}
