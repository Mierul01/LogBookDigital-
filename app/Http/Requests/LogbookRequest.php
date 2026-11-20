<?php

namespace App\Http\Requests;

use App\Models\Logbook;
use App\Models\LogbookAttachment;
use App\Models\Semester;
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
                // One entry per week in the entry's semester (the current one for new entries).
                Rule::unique('logbooks')
                    ->where('student_id', $this->user()->id)
                    ->where('semester_id', $logbook?->semester_id ?? Semester::current()?->id)
                    ->ignore($logbook?->id),
            ],
            'entry_date' => ['required', 'date'],
            'meeting_mode' => ['required', Rule::in(array_keys(Logbook::MEETING_MODES))],
            // Time and length are needed when there was a meeting.
            'meeting_time' => ['exclude_if:meeting_mode,none', 'required', 'date_format:H:i'],
            'meeting_minutes' => ['exclude_if:meeting_mode,none', 'required', 'integer', 'between:5,480'],
            'meeting_place' => ['exclude_if:meeting_mode,none', 'nullable', 'string', 'max:255'],
            'progress' => ['required', 'string', 'max:5000'],
            'current_status' => ['required', 'string', 'max:5000'],
            'problem' => ['required', 'string', 'max:5000'],
            'next_week_task' => ['required', 'string', 'max:5000'],
            // Drawn on a canvas and posted as a PNG data URL. Optional when editing: blank keeps the old one.
            'student_signature' => [$logbook ? 'nullable' : 'required', 'string', 'starts_with:data:image/png;base64,', 'max:500000'],
            // Evidence files. The total per entry (kept + new) is checked in after().
            'attachments' => ['nullable', 'array', 'max:'.LogbookAttachment::MAX_FILES],
            'attachments.*' => ['file', 'max:'.LogbookAttachment::MAX_KB, 'mimes:'.implode(',', LogbookAttachment::EXTENSIONS)],
            'remove_attachments' => ['nullable', 'array'],
            'remove_attachments.*' => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'week_no.unique' => 'You already have an entry for this week. Pick a different week number.',
            'student_signature.required' => 'Please sign in the signature box before submitting.',
            'student_signature.starts_with' => 'The signature could not be read. Please sign again.',
        ];
    }

    /** Kept files plus new files may not go over the limit. */
    public function after(): array
    {
        return [function ($validator) {
            $logbook = $this->route('logbook');
            $kept = $logbook ? $logbook->attachments()->whereNotIn('id', (array) $this->input('remove_attachments', []))->count() : 0;

            if ($kept + count((array) $this->file('attachments', [])) > LogbookAttachment::MAX_FILES) {
                $validator->errors()->add('attachments', 'An entry can have at most '.LogbookAttachment::MAX_FILES.' files. Remove some first.');
            }
        }];
    }

    public function attributes(): array
    {
        return [
            'week_no' => 'week number',
            'attachments.*' => 'file',
            'entry_date' => 'date',
            'meeting_mode' => 'meeting type',
            'meeting_time' => 'meeting time',
            'meeting_minutes' => 'meeting length',
            'meeting_place' => 'place or link',
            'progress' => 'progress / matters discussed',
            'next_week_task' => 'next week\'s task',
        ];
    }
}
