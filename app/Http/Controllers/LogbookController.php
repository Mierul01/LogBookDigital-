<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogbookRequest;
use App\Http\Requests\ReviewLogbookRequest;
use App\Models\Logbook;
use App\Models\LogbookAttachment;
use App\Models\Semester;
use App\Models\User;
use App\Notifications\LogbookActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class LogbookController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $status = $request->query('status');
        $studentId = $request->query('student');
        $search = trim((string) $request->query('q'));

        // Old links used ?status=reviewed for signed entries.
        if ($status === 'reviewed') {
            $status = Logbook::STATUS_APPROVED;
        }

        $supervisorId = $user->isCoordinator() ? $request->query('supervisor') : null;

        // Current semester unless another one (or "all") is picked.
        $semesters = Semester::query()->orderByDesc('starts_on')->get(['id', 'name', 'is_current']);
        $semesterParam = $request->query('semester');
        $semester = $semesterParam === 'all' ? null : ($semesters->firstWhere('id', (int) $semesterParam) ?? $semesters->firstWhere('is_current', true));

        $query = match (true) {
            $user->isCoordinator() => Logbook::query()->select('logbooks.*'),
            $user->isSupervisor() => $user->supervisedLogbooks(),
            default => $user->logbooks(),
        };

        $logbooks = $query
            ->with(['student', 'semester'])
            ->inSemester($semester)
            ->when(in_array($status, [Logbook::STATUS_PENDING, Logbook::STATUS_REVISION, Logbook::STATUS_APPROVED], true),
                fn ($q) => $q->where('logbooks.status', $status))
            ->when($user->isStaff() && $studentId, fn ($q) => $q->where('logbooks.student_id', $studentId))
            ->when($supervisorId, fn ($q) => $q->whereIn('logbooks.student_id', User::query()->select('id')->where('supervisor_id', $supervisorId)))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('logbooks.progress', 'like', "%{$search}%")
                ->orWhere('logbooks.current_status', 'like', "%{$search}%")
                ->orWhere('logbooks.problem', 'like', "%{$search}%")
                ->orWhere('logbooks.next_week_task', 'like', "%{$search}%")))
            ->orderBy('logbooks.week_no')
            ->paginate(10)
            ->withQueryString();

        return view('logbooks.index', [
            'logbooks' => $logbooks,
            'students' => match (true) {
                $user->isCoordinator() => User::query()->where('role', User::ROLE_STUDENT)
                    ->when($supervisorId, fn ($q) => $q->where('supervisor_id', $supervisorId))
                    ->orderBy('name')->get(['id', 'name', 'username']),
                $user->isSupervisor() => $user->students()->orderBy('name')->get(['id', 'name', 'username']),
                default => collect(),
            },
            'supervisors' => $user->isCoordinator() ? User::query()->where('role', User::ROLE_SUPERVISOR)->orderBy('name')->get(['id', 'name']) : collect(),
            'semesters' => $semesters,
            'semester' => $semester,
            'filters' => ['status' => $status, 'student' => $studentId, 'supervisor' => $supervisorId, 'q' => $search, 'semester' => $semesterParam],
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        Gate::authorize('create', Logbook::class);

        $user = $request->user();

        if (! Semester::current()) {
            return $this->noSemester();
        }

        if (! $user->nextLogbookWeek()) {
            return redirect()->route('logbooks.index')
                ->with('success_title', 'All weeks logged')
                ->with('success', 'You have written an entry for every week of the semester.');
        }

        return view('logbooks.form', [
            'logbook' => new Logbook(['week_no' => $user->nextLogbookWeek(), 'entry_date' => now()]),
            'usedWeeks' => $user->logbooks()->inSemester(Semester::current())->pluck('week_no')->all(),
        ]);
    }

    public function store(LogbookRequest $request): RedirectResponse
    {
        Gate::authorize('create', Logbook::class);

        if (! Semester::current()) {
            return $this->noSemester();
        }

        $logbook = $request->user()->logbooks()->create([
            ...$request->safe()->except(['attachments', 'remove_attachments']),
            'semester_id' => Semester::current()?->id,
            'status' => Logbook::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->saveAttachments($request, $logbook);

        $this->notifySupervisor($logbook, 'submitted', "{$logbook->student->name} submitted week {$logbook->week_no}.");

        return redirect()->route('logbooks.show', $logbook)
            ->with('success', "Week {$logbook->week_no} entry sent. Your supervisor can now review it.")
            ->with('success_title', 'Entry submitted!');
    }

    public function show(Logbook $logbook): View
    {
        Gate::authorize('view', $logbook);

        return view('logbooks.show', ['logbook' => $logbook->load(['student.supervisor', 'attachments'])]);
    }

    public function edit(Request $request, Logbook $logbook): View
    {
        Gate::authorize('update', $logbook);

        return view('logbooks.form', [
            'logbook' => $logbook,
            'usedWeeks' => $request->user()->logbooks()->where('semester_id', $logbook->semester_id)->whereKeyNot($logbook->id)->pluck('week_no')->all(),
        ]);
    }

    public function update(LogbookRequest $request, Logbook $logbook): RedirectResponse
    {
        Gate::authorize('update', $logbook);

        $data = $request->safe()->except(['attachments', 'remove_attachments']);
        if (blank($data['student_signature'] ?? null)) {
            unset($data['student_signature']);
        }

        // Fixing an entry that was sent back puts it in front of the supervisor again.
        $resubmitted = $logbook->needsRevision();
        if ($resubmitted) {
            $data += ['status' => Logbook::STATUS_PENDING, 'submitted_at' => now()];
        }

        $logbook->update($data);

        $logbook->attachments()->whereIn('id', (array) $request->input('remove_attachments', []))->get()->each->delete();
        $this->saveAttachments($request, $logbook);

        if ($resubmitted) {
            $this->notifySupervisor($logbook, 'resubmitted', "{$logbook->student->name} fixed and resent week {$logbook->week_no}.");

            return redirect()->route('logbooks.show', $logbook)
                ->with('success', 'Your supervisor will look at it again.')->with('success_title', 'Entry resubmitted');
        }

        return redirect()->route('logbooks.show', $logbook)->with('success', 'Entry updated.')->with('success_title', 'Changes saved');
    }

    public function destroy(Logbook $logbook): RedirectResponse
    {
        Gate::authorize('delete', $logbook);

        $logbook->delete();

        return redirect()->route('logbooks.index')->with('success', "Week {$logbook->week_no} entry deleted.")->with('success_title', 'Entry deleted');
    }

    public function review(ReviewLogbookRequest $request, Logbook $logbook): RedirectResponse
    {
        Gate::authorize('review', $logbook);

        $data = $request->validated();
        $student = $logbook->student;

        if ($data['decision'] === 'revision') {
            $logbook->update([
                'status' => Logbook::STATUS_REVISION,
                'supervisor_comment' => $data['supervisor_comment'],
                'supervisor_signature' => null,
                'reviewed_at' => null,
            ]);

            $student->notify(new LogbookActivity($logbook, 'revision', "Week {$logbook->week_no} was sent back. Please read the comment and fix it."));

            return redirect()->route('logbooks.show', $logbook)
                ->with('success', "Week {$logbook->week_no} was sent back to {$student->name} with your comment.")
                ->with('success_title', 'Sent for revision');
        }

        $logbook->update([
            'status' => Logbook::STATUS_APPROVED,
            'supervisor_comment' => $data['supervisor_comment'],
            'supervisor_signature' => $data['supervisor_signature'],
            'reviewed_at' => now(),
        ]);

        $student->notify(new LogbookActivity($logbook, 'approved', "Week {$logbook->week_no} was approved and signed."));

        return redirect()->route('logbooks.show', $logbook)
            ->with('success', "Week {$logbook->week_no} of {$student->name} is approved and signed.")
            ->with('success_title', 'Entry signed');
    }

    /**
     * The whole semester's logbook for one student on a single printable page.
     * Students see their own; supervisors pick one of their students.
     */
    public function report(Request $request): View
    {
        $user = $request->user();

        $student = match (true) {
            $user->isCoordinator() => User::query()->where('role', User::ROLE_STUDENT)->findOrFail($request->query('student')),
            $user->isSupervisor() => $user->students()->findOrFail($request->query('student')),
            default => $user,
        };

        $semester = Semester::find($request->query('semester')) ?? Semester::current();

        return view('logbooks.report', [
            'student' => $student->load('supervisor'),
            'semester' => $semester,
            'project' => $student->project($semester),
            'logbooks' => $student->logbooks()->inSemester($semester)->with(['attachments', 'semester'])->orderBy('week_no')->get()->keyBy('week_no'),
        ]);
    }

    /** Download an attachment; anyone who may view the entry may download its files. */
    public function attachment(Logbook $logbook, LogbookAttachment $attachment): StreamedResponse
    {
        Gate::authorize('view', $logbook);
        abort_unless($attachment->logbook_id === $logbook->id, 404);

        return Storage::disk('local')->download($attachment->path, $attachment->original_name);
    }

    /** Store uploaded evidence files on the private disk, under the student's folder. */
    private function saveAttachments(Request $request, Logbook $logbook): void
    {
        foreach ((array) $request->file('attachments', []) as $file) {
            $logbook->attachments()->create([
                'original_name' => mb_substr($file->getClientOriginalName(), 0, 255),
                'path' => $file->store("attachments/{$logbook->student_id}/{$logbook->id}", 'local'),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    private function noSemester(): RedirectResponse
    {
        return redirect()->route('logbooks.index')
            ->with('success_title', 'No semester open yet')
            ->with('success', 'Your FYP coordinator has not opened this semester yet. You can write entries once they do.');
    }

    private function notifySupervisor(Logbook $logbook, string $kind, string $message): void
    {
        $logbook->loadMissing('student.supervisor');

        $logbook->student->supervisor?->notify(new LogbookActivity($logbook, $kind, $message));
    }
}
