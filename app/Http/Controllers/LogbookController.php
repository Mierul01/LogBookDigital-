<?php

namespace App\Http\Controllers;

use App\Http\Requests\LogbookRequest;
use App\Http\Requests\ReviewLogbookRequest;
use App\Models\Logbook;
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

        $query = $user->isSupervisor() ? $user->supervisedLogbooks() : $user->logbooks();

        $logbooks = $query
            ->with('student')
            ->when($status === 'reviewed', fn ($q) => $q->whereNotNull('logbooks.reviewed_at'))
            ->when($status === 'pending', fn ($q) => $q->whereNull('logbooks.reviewed_at'))
            ->when($user->isSupervisor() && $studentId, fn ($q) => $q->where('logbooks.student_id', $studentId))
            ->orderBy('logbooks.week_no')
            ->paginate(10)
            ->withQueryString();

        return view('logbooks.index', [
            'logbooks' => $logbooks,
            'students' => $user->isSupervisor() ? $user->students()->orderBy('name')->get() : collect(),
            'filters' => ['status' => $status, 'student' => $studentId],
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Logbook::class);

        $user = $request->user();

        return view('logbooks.form', [
            'logbook' => new Logbook(['week_no' => $user->nextLogbookWeek(), 'entry_date' => now()]),
            'usedWeeks' => $user->logbooks()->pluck('week_no')->all(),
        ]);
    }

    public function store(LogbookRequest $request): RedirectResponse
    {
        Gate::authorize('create', Logbook::class);

        $logbook = $request->user()->logbooks()->create($request->validated());

        return redirect()->route('logbooks.show', $logbook)
            ->with('success', "Week {$logbook->week_no} entry saved. Your supervisor can now review it.")
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

        $logbook->update($request->validated());

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

        $logbook->update([...$request->validated(), 'reviewed_at' => now()]);

        return redirect()->route('logbooks.show', $logbook)
            ->with('success', "Week {$logbook->week_no} of {$logbook->student->name} marked as reviewed.")
            ->with('success_title', 'Entry signed');
    }
}
