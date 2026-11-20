<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\Logbook;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The FYP coordinator creates and manages every account: students, supervisors and other coordinators.
 */
class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $role = in_array($request->query('role'), User::ROLES, true) ? $request->query('role') : null;
        $supervisorId = $request->query('supervisor');

        $users = User::query()
            ->with('supervisor:id,name')
            ->withCount([
                'logbooks',
                'logbooks as approved_count' => fn ($q) => $q->where('status', Logbook::STATUS_APPROVED),
                'students',
            ])
            ->when($role, fn ($q) => $q->where('role', $role))
            ->when($supervisorId, fn ($q) => $q->where('supervisor_id', $supervisorId))
            ->when($search, fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'supervisors' => $this->supervisors(),
            'counts' => User::query()->selectRaw('role, count(*) as total')->groupBy('role')->pluck('total', 'role'),
            'filters' => ['q' => $search, 'role' => $role, 'supervisor' => $supervisorId],
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.users.form', [
            'account' => new User(['role' => $request->query('role', User::ROLE_STUDENT)]),
            'supervisors' => $this->supervisors(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $account = User::create($request->accountData());

        return redirect()->route('admin.users.index', ['role' => $account->role])
            ->with('success_title', 'Account created')
            ->with('success', "{$account->name} ({$account->username}) can now log in as ".$account->role.'.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['account' => $user, 'supervisors' => $this->supervisors()]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $user->update($request->accountData());

        return redirect()->route('admin.users.index', ['role' => $user->role])
            ->with('success_title', 'Account updated')
            ->with('success', "{$user->name}'s details were saved.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        abort_if($user->is($request->user()), 422, 'You cannot delete your own account.');

        // Students keep their logbook only while their account exists; supervisors' students become unassigned.
        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success_title', 'Account removed')
            ->with('success', "{$user->name} was removed.");
    }

    /** @return \Illuminate\Support\Collection<int, User> */
    private function supervisors()
    {
        return User::query()->where('role', User::ROLE_SUPERVISOR)->orderBy('name')->get(['id', 'name', 'username']);
    }
}
