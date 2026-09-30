<?php

namespace App\Policies;

use App\Models\Logbook;
use App\Models\User;

class LogbookPolicy
{
    /** Students and supervisors can both list logbooks (scoped in the controller). */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /** Owner student, or the student's supervisor. */
    public function view(User $user, Logbook $logbook): bool
    {
        return $this->owns($user, $logbook) || $this->supervises($user, $logbook);
    }

    /** Only students write logbook entries. */
    public function create(User $user): bool
    {
        return $user->isStudent();
    }

    /** Students may edit their own entry until the supervisor has reviewed it. */
    public function update(User $user, Logbook $logbook): bool
    {
        return $this->owns($user, $logbook) && ! $logbook->isReviewed();
    }

    /** Students may delete their own entries; supervisors never delete. */
    public function delete(User $user, Logbook $logbook): bool
    {
        return $this->owns($user, $logbook);
    }

    /** The assigned supervisor comments on and signs an entry. */
    public function review(User $user, Logbook $logbook): bool
    {
        return $this->supervises($user, $logbook);
    }

    private function owns(User $user, Logbook $logbook): bool
    {
        return $user->isStudent() && $logbook->student_id === $user->id;
    }

    private function supervises(User $user, Logbook $logbook): bool
    {
        return $user->isSupervisor() && $logbook->student?->supervisor_id === $user->id;
    }
}
