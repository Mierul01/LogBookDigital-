<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_STUDENT = 'student';

    public const ROLE_SUPERVISOR = 'supervisor';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'name',
        'email',
        'role',
        'supervisor_id',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    /** The supervisor a student is assigned to. */
    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /** Students assigned to a supervisor. */
    public function students(): HasMany
    {
        return $this->hasMany(User::class, 'supervisor_id')->where('role', self::ROLE_STUDENT);
    }

    /** A student's own logbook entries. */
    public function logbooks(): HasMany
    {
        return $this->hasMany(Logbook::class, 'student_id');
    }

    /** Logbook entries of every student under a supervisor. */
    public function supervisedLogbooks(): HasManyThrough
    {
        return $this->hasManyThrough(Logbook::class, User::class, 'supervisor_id', 'student_id');
    }

    /** First week number the student hasn't logged yet, or null when every week is done. */
    public function nextLogbookWeek(): ?int
    {
        $usedWeeks = $this->logbooks()->pluck('week_no')->all();

        return collect(range(1, Logbook::MAX_WEEKS))->first(fn (int $week) => ! in_array($week, $usedWeeks));
    }

    public function initials(): string
    {
        return collect(explode(' ', $this->name))
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }
}
