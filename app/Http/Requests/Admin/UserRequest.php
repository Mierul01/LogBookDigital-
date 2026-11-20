<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    /** The route is already limited to coordinators. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $account = $this->route('user');

        return [
            'role' => ['required', Rule::in(User::ROLES)],
            // Letters, numbers, dots, dashes and underscores (e.g. A192910, dr.aisyah).
            'username' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('users')->ignore($account?->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($account?->id)],
            // Only students have a supervisor.
            'supervisor_id' => ['nullable', 'exclude_unless:role,'.User::ROLE_STUDENT,
                Rule::exists('users', 'id')->where('role', User::ROLE_SUPERVISOR)],
            'password' => [$account ? 'nullable' : 'required', Password::min(8)],
        ];
    }

    public function attributes(): array
    {
        return ['username' => 'username / matric no.', 'supervisor_id' => 'supervisor'];
    }

    protected function prepareForValidation(): void
    {
        // Matric numbers are stored upper-case, like everywhere else in the app.
        if ($this->input('role') === User::ROLE_STUDENT) {
            $this->merge(['username' => strtoupper(trim((string) $this->username))]);
        }
    }

    /** Validated data ready for create/update: a blank password keeps the old one. */
    public function accountData(): array
    {
        $data = $this->validated();

        if (($data['role'] ?? null) !== User::ROLE_STUDENT) {
            $data['supervisor_id'] = null;
        }
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        return $data;
    }
}
