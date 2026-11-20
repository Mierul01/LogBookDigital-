<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $weeks = range(1, Logbook::MAX_WEEKS);

        // Every query below is scoped to the student's own entries, or the supervisor's students' entries.
        $scope = fn (): Builder => Logbook::query()->when(
            $user->isSupervisor(),
            fn ($q) => $q->whereIn('student_id', User::query()->select('id')->where('supervisor_id', $user->id)->where('role', User::ROLE_STUDENT)),
            fn ($q) => $q->where('student_id', $user->id),
        );

        // Counts per week and status, aggregated in the database (stays small with many students).
        $matrix = $scope()
            ->selectRaw('week_no, status, count(*) as total')
            ->groupBy('week_no', 'status')
            ->get();

        $perWeek = fn (?string $status = null) => array_map(
            fn (int $week) => (int) $matrix->where('week_no', $week)
                ->when($status, fn ($rows) => $rows->where('status', $status))
                ->sum('total'),
            $weeks,
        );

        $byStatus = [
            Logbook::STATUS_APPROVED => (int) $matrix->where('status', Logbook::STATUS_APPROVED)->sum('total'),
            Logbook::STATUS_PENDING => (int) $matrix->where('status', Logbook::STATUS_PENDING)->sum('total'),
            Logbook::STATUS_REVISION => (int) $matrix->where('status', Logbook::STATUS_REVISION)->sum('total'),
        ];
        $total = array_sum($byStatus);
        $students = $user->isSupervisor() ? $user->students()->count() : null;

        // Average hours from submission to approval, over the latest 200 approvals.
        $reviewHours = $scope()
            ->where('status', Logbook::STATUS_APPROVED)
            ->whereNotNull('submitted_at')
            ->latest('reviewed_at')
            ->take(200)
            ->get(['submitted_at', 'reviewed_at'])
            ->avg(fn (Logbook $l) => max(0, $l->submitted_at->diffInMinutes($l->reviewed_at)) / 60);

        $thisWeek = $scope()->where('created_at', '>=', now()->subDays(7))->count();

        return view('dashboard', [
            'user' => $user,
            'weeks' => $weeks,
            'stats' => [
                'total' => $total,
                'reviewed' => $byStatus[Logbook::STATUS_APPROVED],
                'pending' => $byStatus[Logbook::STATUS_PENDING],
                'revision' => $byStatus[Logbook::STATUS_REVISION],
                'students' => $students,
                'weeks' => Logbook::MAX_WEEKS,
                'next_week' => $user->isStudent() ? $user->nextLogbookWeek() : null,
                'this_week' => $thisWeek,
                'approval_rate' => $total ? round($byStatus[Logbook::STATUS_APPROVED] / $total * 100) : 0,
                'review_hours' => $reviewHours,
                // Entries the supervisor could expect so far (each student x weeks).
                'expected' => $user->isSupervisor() ? $students * Logbook::MAX_WEEKS : Logbook::MAX_WEEKS,
            ],
            'series' => [
                'submitted' => $perWeek(),
                'approved' => $perWeek(Logbook::STATUS_APPROVED),
                'pending' => $perWeek(Logbook::STATUS_PENDING),
                'revision' => $perWeek(Logbook::STATUS_REVISION),
            ],
            'recent' => $scope()->with('student')->latest('updated_at')->latest('id')->take(5)->get(),
            'activities' => $user->notifications()->latest()->take(5)->get(),
            // Student: entries keyed by week number for the timeline.
            'timeline' => $user->isStudent() ? $user->logbooks()->get(['id', 'week_no', 'status'])->keyBy('week_no') : collect(),
            // Supervisor: students with the most approved weeks.
            'topStudents' => $user->isSupervisor()
                ? $user->students()
                    ->withCount([
                        'logbooks',
                        'logbooks as reviewed_count' => fn ($q) => $q->where('status', Logbook::STATUS_APPROVED),
                    ])
                    ->orderByDesc('reviewed_count')
                    ->orderBy('name')
                    ->take(5)
                    ->get()
                : collect(),
            // Supervisor: oldest entries still waiting. Student: latest supervisor comments.
            'queue' => $user->isSupervisor()
                ? $scope()->with('student')->where('status', Logbook::STATUS_PENDING)->oldest('submitted_at')->take(5)->get()
                : $scope()->whereNotNull('supervisor_comment')->latest('updated_at')->take(5)->get(),
        ]);
    }
}
