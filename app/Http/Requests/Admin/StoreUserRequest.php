<?php

namespace App\Http\Requests\Admin;

use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8'],
            'area_id' => ['nullable', 'exists:areas,id'],
            'position' => ['nullable', 'string', 'max:255'],
            'active' => ['boolean'],
            'role' => ['required', Rule::in(RoleSeeder::ROLES)],
        ];
    }
}
