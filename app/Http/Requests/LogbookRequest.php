<?php

namespace App\Http\Requests;

use App\Models\Logbook;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LogbookRequest extends FormRequest
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
        $logbook = $this->route('logbook');

        return [
            'week_no' => [
                'required', 'integer', 'between:1,'.Logbook::MAX_WEEKS,
                Rule::unique('logbooks')
                    ->where('student_id', $this->user()->id)
                    ->ignore($logbook?->id),
            ],
            'entry_date' => ['required', 'date'],
            'progress' => ['required', 'string', 'max:5000'],
            'current_status' => ['required', 'string', 'max:5000'],
            'problem' => ['required', 'string', 'max:5000'],
            'next_week_task' => ['required', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'week_no.unique' => 'You already have an entry for this week. Pick a different week number.',
        ];
    }

    public function attributes(): array
    {
        return [
            'week_no' => 'week number',
            'entry_date' => 'date',
            'progress' => 'progress / matters discussed',
            'next_week_task' => 'next week\'s task',
        ];
    }
}
