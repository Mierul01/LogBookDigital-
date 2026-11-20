<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogbookRequest;
use App\Http\Requests\ReviewLogbookRequest;
use App\Models\Logbook;
use App\Notifications\LogbookActivity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        $query = $user->isSupervisor() ? $user->supervisedLogbooks() : $user->logbooks();

        $logbooks = $query
            ->with('student')
            ->when(in_array($status, [Logbook::STATUS_PENDING, Logbook::STATUS_REVISION, Logbook::STATUS_APPROVED], true),
                fn ($q) => $q->where('logbooks.status', $status))
            ->when($user->isSupervisor() && $studentId, fn ($q) => $q->where('logbooks.student_id', $studentId))
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
            'students' => $user->isSupervisor() ? $user->students()->orderBy('name')->get(['id', 'name', 'username']) : collect(),
            'filters' => ['status' => $status, 'student' => $studentId, 'q' => $search],
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        Gate::authorize('create', Logbook::class);

        $user = $request->user();

        if (! $user->nextLogbookWeek()) {
            return redirect()->route('logbooks.index')
                ->with('success_title', 'All weeks logged')
                ->with('success', 'You have written an entry for every week of the semester.');
        }

        return view('logbooks.form', [
            'logbook' => new Logbook(['week_no' => $user->nextLogbookWeek(), 'entry_date' => now()]),
            'usedWeeks' => $user->logbooks()->pluck('week_no')->all(),
        ]);
    }

    public function store(LogbookRequest $request): RedirectResponse
    {
        Gate::authorize('create', Logbook::class);

        $logbook = $request->user()->logbooks()->create([
            ...$request->validated(),
            'status' => Logbook::STATUS_PENDING,
            'submitted_at' => now(),
        ]);

        $this->notifySupervisor($logbook, 'submitted', "{$logbook->student->name} submitted week {$logbook->week_no}.");

        return redirect()->route('logbooks.show', $logbook)
            ->with('success', "Week {$logbook->week_no} entry sent. Your supervisor can now review it.")
            ->with('success_title', 'Entry submitted!');
    }

    public function show(Logbook $logbook): View
    {
        Gate::authorize('view', $logbook);

        return view('logbooks.show', ['logbook' => $logbook->load('student.supervisor')]);
    }

    public function edit(Request $request, Logbook $logbook): View
    {
        Gate::authorize('update', $logbook);

        return view('logbooks.form', [
            'logbook' => $logbook,
            'usedWeeks' => $request->user()->logbooks()->whereKeyNot($logbook->id)->pluck('week_no')->all(),
        ]);
    }

    public function update(LogbookRequest $request, Logbook $logbook): RedirectResponse
    {
        Gate::authorize('update', $logbook);

        $data = $request->validated();
        if (blank($data['student_signature'] ?? null)) {
            unset($data['student_signature']);
        }

        // Fixing an entry that was sent back puts it in front of the supervisor again.
        $resubmitted = $logbook->needsRevision();
        if ($resubmitted) {
            $data += ['status' => Logbook::STATUS_PENDING, 'submitted_at' => now()];
        }

        $logbook->update($data);

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

        $student = $user->isSupervisor()
            ? $user->students()->findOrFail($request->query('student'))
            : $user;

        return view('logbooks.report', [
            'student' => $student->load('supervisor'),
            'logbooks' => $student->logbooks()->orderBy('week_no')->get()->keyBy('week_no'),
        ]);
    }

    private function notifySupervisor(Logbook $logbook, string $kind, string $message): void
    {
        $logbook->loadMissing('student.supervisor');

        $logbook->student->supervisor?->notify(new LogbookActivity($logbook, $kind, $message));
    }
}
