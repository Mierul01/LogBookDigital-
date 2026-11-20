<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            // approve = sign and lock the entry; revision = send it back to the student with the comment.
            'decision' => ['required', Rule::in(['approve', 'revision'])],
            'supervisor_comment' => ['required', 'string', 'max:5000'],
            // Drawn on a canvas and posted as a PNG data URL. Only needed to approve.
            'supervisor_signature' => ['exclude_unless:decision,approve', 'required', 'string', 'starts_with:data:image/png;base64,', 'max:500000'],
        ];
    }

    public function messages(): array
    {
        return [
            'supervisor_comment.required' => 'Please write a comment for the student.',
            'supervisor_signature.required' => 'Please sign in the signature box before approving.',
            'supervisor_signature.starts_with' => 'The signature could not be read. Please sign again.',
        ];
    }

    public function attributes(): array
    {
        return [
            'supervisor_comment' => 'comment',
        ];
    }

    /** Older forms only sent a signature, which meant "approve". */
    protected function prepareForValidation(): void
    {
        if (! $this->has('decision')) {
            $this->merge(['decision' => 'approve']);
        }
    }
}
