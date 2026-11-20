<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Logbook extends Model
{
    use HasFactory;

    public const MAX_WEEKS = 14;

    /** Waiting for the supervisor to read it. */
    public const STATUS_PENDING = 'pending';

    /** The supervisor sent it back for the student to fix. */
    public const STATUS_REVISION = 'revision';

    /** The supervisor approved and signed it. */
    public const STATUS_APPROVED = 'approved';

    /** How the student met the supervisor that week. */
    public const MEETING_MODES = [
        'face_to_face' => 'Face to face',
        'online' => 'Online',
        'none' => 'No meeting this week',
    ];

    protected $fillable = [
        'student_id',
        'semester_id',
        'week_no',
        'entry_date',
        'meeting_mode',
        'meeting_time',
        'meeting_minutes',
        'meeting_place',
        'progress',
        'current_status',
        'problem',
        'next_week_task',
        'status',
        'student_signature',
        'submitted_at',
        'supervisor_comment',
        'supervisor_signature',
        'reviewed_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'week_no' => 'integer',
            'entry_date' => 'date',
            'meeting_minutes' => 'integer',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Delete attachment files along with the entry.
        static::deleting(fn (self $logbook) => $logbook->attachments->each->delete());
    }

    /** History, oldest first. */
    public function events(): HasMany
    {
        return $this->hasMany(LogbookEvent::class)->oldest('created_at')->oldest('id');
    }

    /** Add a step to the entry's history. */
    public function record(string $action, ?User $by, ?array $changes = null, ?string $note = null): void
    {
        $this->events()->create([
            'user_id' => $by?->id,
            'action' => $action,
            'changes' => $changes ?: null,
            'note' => $note,
        ]);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LogbookAttachment::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    /** Entries of one semester; with no semester given, every entry. */
    public function scopeInSemester(Builder $query, ?Semester $semester): void
    {
        $query->when($semester, fn ($q) => $q->where($q->qualifyColumn('semester_id'), $semester->id));
    }

    /** Approved and signed by the supervisor; the entry is locked. */
    public function isReviewed(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    /** First submitted after the week's deadline. */
    public function isLate(): bool
    {
        return $this->semester !== null && $this->created_at !== null
            && $this->created_at->gt($this->semester->dueAt($this->week_no));
    }

    public function hadMeeting(): bool
    {
        return $this->meeting_mode !== 'none';
    }

    /** e.g. "Face to face · 2:30 pm · 45 min · Bilik Perbincangan 2" */
    public function meetingSummary(): string
    {
        if (! $this->hadMeeting()) {
            return self::MEETING_MODES['none'];
        }

        return collect([
            self::MEETING_MODES[$this->meeting_mode] ?? $this->meeting_mode,
            $this->meeting_time ? \Illuminate\Support\Carbon::parse($this->meeting_time)->format('g:i a') : null,
            $this->meeting_minutes ? $this->meeting_minutes.' min' : null,
            $this->meeting_place,
        ])->filter()->implode(' · ');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function needsRevision(): bool
    {
        return $this->status === self::STATUS_REVISION;
    }
}
