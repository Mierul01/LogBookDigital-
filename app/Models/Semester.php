<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'starts_on', 'is_current'];

    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'is_current' => 'boolean',
        ];
    }

    /** The semester new logbook entries go into (set by the coordinator). */
    public static function current(): ?self
    {
        return static::query()->where('is_current', true)->latest('starts_on')->first();
    }

    /** Make this the only current semester. */
    public function makeCurrent(): void
    {
        static::query()->whereKeyNot($this->id)->update(['is_current' => false]);
        $this->update(['is_current' => true]);
    }

    public function logbooks(): HasMany
    {
        return $this->hasMany(Logbook::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /** Monday of the given week. */
    public function weekStart(int $week): Carbon
    {
        return $this->starts_on->copy()->addWeeks($week - 1)->startOfDay();
    }

    /** Last day of the semester. */
    public function endsOn(): Carbon
    {
        return $this->weekStart(Logbook::MAX_WEEKS)->addDays(6);
    }

    /** The teaching week we are in today, clamped to 1..14 (0 before the semester starts). */
    public function currentWeek(): int
    {
        if (now()->lt($this->starts_on)) {
            return 0;
        }

        return min(Logbook::MAX_WEEKS, (int) floor($this->starts_on->diffInDays(now()) / 7) + 1);
    }
}
