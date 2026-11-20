<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A student's final year project in one semester. */
class Project extends Model
{
    use HasFactory;

    public const STAGES = ['FYP1' => 'FYP 1', 'FYP2' => 'FYP 2'];

    protected $fillable = ['student_id', 'semester_id', 'title', 'stage'];

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function stageLabel(): string
    {
        return self::STAGES[$this->stage] ?? $this->stage;
    }
}
