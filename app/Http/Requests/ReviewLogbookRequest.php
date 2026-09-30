<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReviewLogbookRequest extends FormRequest
{
    /** Authorization is handled by LogbookPolicy in the controller. */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'supervisor_comment' => ['required', 'string', 'max:5000'],
            // Drawn on a canvas and posted as a PNG data URL.
            'supervisor_signature' => ['required', 'string', 'starts_with:data:image/png;base64,', 'max:500000'],
        ];
    }

    public function messages(): array
    {
        return [
            'supervisor_signature.required' => 'Please sign in the signature box before saving.',
            'supervisor_signature.starts_with' => 'The signature could not be read. Please sign again.',
        ];
    }

    public function attributes(): array
    {
        return [
            'supervisor_comment' => 'comment',
        ];
    }
}
