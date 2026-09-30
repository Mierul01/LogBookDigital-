<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Logbook extends Model
{
    use HasFactory;

    public const MAX_WEEKS = 14;

    protected $fillable = [
        'student_id',
        'week_no',
        'entry_date',
        'progress',
        'current_status',
        'problem',
        'next_week_task',
        'supervisor_comment',
        'supervisor_signature',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'week_no' => 'integer',
            'entry_date' => 'date',
            'reviewed_at' => 'datetime',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** An entry is reviewed once the supervisor has commented and signed it. */
    public function isReviewed(): bool
    {
        return $this->reviewed_at !== null;
    }
}
