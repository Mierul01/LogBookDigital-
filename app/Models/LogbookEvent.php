<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One step in an entry's history. Never edited after it is written. */
class LogbookEvent extends Model
{
    public const UPDATED_AT = null;

    /** Fields whose before/after text is kept when a student edits an entry. */
    public const TRACKED = [
        'week_no' => 'Week',
        'entry_date' => 'Date',
        'meeting_mode' => 'Meeting',
        'meeting_time' => 'Meeting time',
        'meeting_minutes' => 'Meeting length',
        'meeting_place' => 'Meeting place',
        'progress' => 'Progress',
        'current_status' => 'Current status',
        'problem' => 'Problems',
        'next_week_task' => 'Next week\'s task',
    ];

    public const LABELS = [
        'submitted' => 'Submitted and signed',
        'edited' => 'Edited',
        'resubmitted' => 'Fixed and resent',
        'sent_back' => 'Sent back for revision',
        'approved' => 'Approved and signed',
        'files' => 'Files changed',
    ];

    protected $fillable = ['logbook_id', 'user_id', 'action', 'changes', 'note'];

    protected function casts(): array
    {
        return ['changes' => 'array', 'created_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function label(): string
    {
        return self::LABELS[$this->action] ?? ucfirst(str_replace('_', ' ', $this->action));
    }

    private static function normalise(string $field, mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            'entry_date' => substr((string) $value, 0, 10),
            'meeting_time' => substr((string) $value, 0, 5),
            default => $value,
        };
    }

    /**
     * Before/after values of the tracked fields that differ.
     *
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public static function diff(array $before, array $after): array
    {
        $changes = [];
        foreach (array_keys(self::TRACKED) as $field) {
            if (! array_key_exists($field, $after)) {
                continue;
            }
            $old = self::normalise($field, $before[$field] ?? null);
            $new = self::normalise($field, $after[$field]);
            if ((string) $old !== (string) $new) {
                $changes[$field] = [$old, $new];
            }
        }

        return $changes;
    }
}
