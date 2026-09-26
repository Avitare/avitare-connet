<?php

namespace App\Http\Requests\Admin;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => ['nullable', 'string', 'min:8'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
            'role' => ['required', Rule::in(RoleSeeder::ROLES)],
        ];
    }
}
