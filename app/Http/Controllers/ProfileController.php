<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use App\Models\Project;
use App\Models\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user()->load('supervisor');

        $logbooks = $user->isSupervisor() ? $user->supervisedLogbooks() : $user->logbooks();
        $total = (clone $logbooks)->count();
        $reviewed = (clone $logbooks)->whereNotNull('logbooks.reviewed_at')->count();

        $semester = Semester::current();

        return view('profile', [
            'user' => $user,
            'semester' => $semester,
            'project' => $user->isStudent() ? $user->project($semester) : null,
            'stats' => [
                'total' => $total,
                'reviewed' => $reviewed,
                'students' => $user->isSupervisor() ? $user->students()->count() : null,
                'weeks' => Logbook::MAX_WEEKS,
                'last_entry' => (clone $logbooks)->latest('logbooks.updated_at')->first()?->updated_at,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
        ]);

        $user->update($data);

        return back()->with('success_title', 'Profile updated')->with('success', 'Your details have been saved.');
    }

    /** The student's project title and stage for the current semester. */
    public function project(Request $request): RedirectResponse
    {
        $semester = Semester::current();
        abort_unless($semester, 422, 'There is no current semester yet. Ask your FYP coordinator.');

        $data = $request->validateWithBag('project', [
            'title' => ['required', 'string', 'max:255'],
            'stage' => ['required', Rule::in(array_keys(Project::STAGES))],
        ], [], ['title' => 'project title']);

        $request->user()->projects()->updateOrCreate(['semester_id' => $semester->id], $data);

        return back()->with('success_title', 'Project saved')->with('success', 'Your project title is now on your logbook.');
    }

    public function password(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('password', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(6)],
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success_title', 'Password changed')->with('success', 'Use your new password next time you log in.');
    }
}
