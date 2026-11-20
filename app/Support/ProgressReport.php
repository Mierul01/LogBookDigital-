<?php

namespace App\Support;

use App\Models\Logbook;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Logbook progress numbers per student and per supervisor, for the Reports page and its CSV exports.
 */
class ProgressReport
{
    public function __construct(public readonly User $viewer, public readonly ?Semester $semester) {}

    /** Students the viewer may report on: a supervisor's own, or (coordinator) everyone. */
    public function students(?string $search = null, ?int $supervisorId = null): Builder
    {
        return User::query()
            ->where('role', User::ROLE_STUDENT)
            ->when($this->viewer->isSupervisor(), fn ($q) => $q->where('supervisor_id', $this->viewer->id))
            ->when($this->viewer->isCoordinator() && $supervisorId, fn ($q) => $q->where('supervisor_id', $supervisorId))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")))
            ->with([
                'supervisor:id,name',
                'projects' => fn ($q) => $q->where('semester_id', $this->semester?->id),
                'logbooks' => fn ($q) => $q->inSemester($this->semester)
                    ->select('id', 'student_id', 'semester_id', 'week_no', 'status', 'meeting_mode', 'created_at', 'entry_date'),
                'logbooks.semester',
            ])
            ->orderBy('name');
    }

    /**
     * One row of numbers per student.
     *
     * @param  iterable<User>  $students
     * @return Collection<int, array<string, mixed>>
     */
    public function rows(iterable $students): Collection
    {
        $pastDue = $this->semester?->weeksPastDue() ?? 0;

        return collect($students)->map(function (User $student) use ($pastDue) {
            $logbooks = $student->logbooks;
            $project = $student->projects->first();
            $loggedDue = $logbooks->where('week_no', '<=', $pastDue)->count();

            return [
                'student' => $student,
                'matric' => $student->username,
                'name' => $student->name,
                'supervisor' => $student->supervisor?->name,
                'project' => $project?->title,
                'stage' => $project?->stageLabel(),
                'entries' => $logbooks->count(),
                'approved' => $logbooks->where('status', Logbook::STATUS_APPROVED)->count(),
                'pending' => $logbooks->where('status', Logbook::STATUS_PENDING)->count(),
                'revision' => $logbooks->where('status', Logbook::STATUS_REVISION)->count(),
                'late' => $logbooks->filter->isLate()->count(),
                'overdue' => max(0, $pastDue - $loggedDue),
                'meetings' => $logbooks->filter->hadMeeting()->count(),
                'last_entry' => $logbooks->max('entry_date'),
            ];
        })->values();
    }

    /** Per-supervisor totals (coordinator only). */
    public function supervisors(): Builder
    {
        $semester = $this->semester;
        $pastDue = $semester?->weeksPastDue() ?? 0;

        return User::query()
            ->where('role', User::ROLE_SUPERVISOR)
            ->withCount([
                'students',
                'supervisedLogbooks as entries_count' => fn ($q) => $q->inSemester($semester),
                'supervisedLogbooks as approved_count' => fn ($q) => $q->inSemester($semester)->where('logbooks.status', Logbook::STATUS_APPROVED),
                'supervisedLogbooks as pending_count' => fn ($q) => $q->inSemester($semester)->where('logbooks.status', Logbook::STATUS_PENDING),
                'supervisedLogbooks as due_logged' => fn ($q) => $q->inSemester($semester)->where('logbooks.week_no', '<=', $pastDue),
            ])
            ->orderBy('name');
    }

    public function pastDue(): int
    {
        return $this->semester?->weeksPastDue() ?? 0;
    }
}
