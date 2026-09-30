<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Supervisors register and manage the students they supervise.
 */
class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));

        $students = $request->user()->students()
            ->withCount([
                'logbooks',
                'logbooks as reviewed_count' => fn ($q) => $q->whereNotNull('reviewed_at'),
            ])
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('students.index', ['students' => $students, 'search' => $search]);
    }

    public function create(): View
    {
        return view('students.form', ['student' => new User]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $student = $request->user()->students()->create([
            ...$request->validated(),
            'role' => User::ROLE_STUDENT,
        ]);

        return redirect()->route('students.index')->with('success', "{$student->name} ({$student->username}) registered.");
    }

    public function edit(Request $request, User $student): View
    {
        $this->ensureSupervises($request, $student);

        return view('students.form', ['student' => $student]);
    }

    public function update(StudentRequest $request, User $student): RedirectResponse
    {
        $this->ensureSupervises($request, $student);

        $data = $request->validated();
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $student->update($data);

        return redirect()->route('students.index')->with('success', "{$student->name} updated.");
    }

    public function destroy(Request $request, User $student): RedirectResponse
    {
        $this->ensureSupervises($request, $student);

        $student->delete();

        return redirect()->route('students.index')->with('success', "{$student->name} and their logbook entries were removed.");
    }

    private function ensureSupervises(Request $request, User $student): void
    {
        abort_unless($student->isStudent() && $student->supervisor_id === $request->user()->id, 404);
    }
}
