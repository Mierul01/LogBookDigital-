<?php

use App\Models\Logbook;
use App\Models\Semester;
use App\Models\User;
use App\Notifications\DeadlineReminder;
use App\Notifications\PendingReviewDigest;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Daily logbook reminders (run by the scheduler, see below):
 *  - students who haven't written the week that is due today;
 *  - on Mondays (or with --digest), supervisors with entries waiting for review.
 */
Artisan::command('logbook:remind {--digest : Send the supervisor summary even if today is not Monday}', function () {
    $semester = Semester::current();
    if (! $semester) {
        $this->info('No current semester; nothing to send.');

        return;
    }

    $week = collect(range(1, Logbook::MAX_WEEKS))->first(fn (int $w) => $semester->dueAt($w)->isToday());
    $reminded = 0;

    if ($week) {
        User::query()
            ->where('role', User::ROLE_STUDENT)
            ->whereDoesntHave('logbooks', fn ($q) => $q->where('semester_id', $semester->id)->where('week_no', $week))
            ->chunkById(200, function ($students) use ($semester, $week, &$reminded) {
                foreach ($students as $student) {
                    $student->notify(new DeadlineReminder($semester, $week));
                    $reminded++;
                }
            });
    }

    $digests = 0;
    if (now()->isMonday() || $this->option('digest')) {
        User::query()
            ->where('role', User::ROLE_SUPERVISOR)
            ->withCount(['supervisedLogbooks as pending_count' => fn ($q) => $q->inSemester($semester)->where('logbooks.status', Logbook::STATUS_PENDING)])
            ->withMin(['supervisedLogbooks as oldest_pending' => fn ($q) => $q->inSemester($semester)->where('logbooks.status', Logbook::STATUS_PENDING)], 'logbooks.submitted_at')
            ->chunkById(200, function ($supervisors) use (&$digests) {
                foreach ($supervisors->where('pending_count', '>', 0) as $supervisor) {
                    $oldest = $supervisor->oldest_pending ? (int) \Illuminate\Support\Carbon::parse($supervisor->oldest_pending)->diffInDays(now()) : 0;
                    $supervisor->notify(new PendingReviewDigest($supervisor->pending_count, $oldest));
                    $digests++;
                }
            });
    }

    $this->info("Sent {$reminded} deadline ".str('reminder')->plural($reminded)." and {$digests} supervisor ".str('summary')->plural($digests).'.');
})->purpose('Send logbook deadline reminders and supervisor summaries');

Schedule::command('logbook:remind')->dailyAt('08:00');
