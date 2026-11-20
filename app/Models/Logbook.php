<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    protected $fillable = [
        'student_id',
        'semester_id',
        'week_no',
        'entry_date',
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
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
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

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function needsRevision(): bool
    {
        return $this->status === self::STATUS_REVISION;
    }
}
