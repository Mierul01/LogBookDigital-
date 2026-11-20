<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The FYP coordinator sets up semesters and picks the current one, which new logbook entries go into.
 */
class SemesterController extends Controller
{
    public function index(): View
    {
        return view('admin.semesters.index', [
            'semesters' => Semester::query()
                ->withCount(['logbooks', 'projects'])
                ->orderByDesc('starts_on')
                ->paginate(10),
        ]);
    }

    public function create(): View
    {
        return view('admin.semesters.form', ['semester' => new Semester(['starts_on' => now()->startOfWeek(), 'due_days' => 3])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $semester = Semester::create($this->validated($request));

        if ($request->boolean('is_current') || ! Semester::current()) {
            $semester->makeCurrent();
        }

        return redirect()->route('admin.semesters.index')
            ->with('success_title', 'Semester added')
            ->with('success', "{$semester->name} was created.");
    }

    public function edit(Semester $semester): View
    {
        return view('admin.semesters.form', ['semester' => $semester]);
    }

    public function update(Request $request, Semester $semester): RedirectResponse
    {
        $semester->update($this->validated($request));

        if ($request->boolean('is_current')) {
            $semester->makeCurrent();
        }

        return redirect()->route('admin.semesters.index')
            ->with('success_title', 'Semester updated')
            ->with('success', "{$semester->name} was saved.");
    }

    /** New entries go into this semester from now on. */
    public function makeCurrent(Semester $semester): RedirectResponse
    {
        $semester->makeCurrent();

        return back()
            ->with('success_title', 'Current semester changed')
            ->with('success', "New logbook entries now go into {$semester->name}.");
    }

    public function destroy(Semester $semester): RedirectResponse
    {
        abort_if($semester->logbooks()->exists(), 422, 'This semester has logbook entries and cannot be deleted.');
        abort_if($semester->is_current, 422, 'Make another semester current before deleting this one.');

        $semester->delete();

        return redirect()->route('admin.semesters.index')
            ->with('success_title', 'Semester deleted')
            ->with('success', "{$semester->name} was removed.");
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'starts_on' => ['required', 'date'],
            'due_days' => ['required', 'integer', 'between:0,14'],
        ], [], ['starts_on' => 'start date', 'due_days' => 'days to submit']);
    }
}
