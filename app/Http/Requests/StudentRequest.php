<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StudentRequest extends FormRequest
{
    /** Route is already restricted to supervisors; ownership is checked in the controller. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $student = $this->route('student');

        return [
            'username' => ['required', 'string', 'max:50', 'alpha_num', Rule::unique('users')->ignore($student?->id)],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($student?->id)],
            // Password is optional when editing: leave blank to keep the current one.
            'password' => [$student ? 'nullable' : 'required', Password::min(6)],
        ];
    }

    public function attributes(): array
    {
        return [
            'username' => 'matric number',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['username' => strtoupper(trim((string) $this->username))]);
    }
}
