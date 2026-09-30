<?php

namespace App\Http\Controllers;

use App\Models\Logbook;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $logbooks = $user->isSupervisor()
            ? $user->supervisedLogbooks()
            : $user->logbooks();

        $total = (clone $logbooks)->count();
        $reviewed = (clone $logbooks)->whereNotNull('logbooks.reviewed_at')->count();

        $recent = (clone $logbooks)
            ->with('student')
            ->latest('logbooks.updated_at')
            ->latest('logbooks.id')
            ->take(5)
            ->get();

        return view('dashboard', [
            'user' => $user,
            'stats' => [
                'total' => $total,
                'reviewed' => $reviewed,
                'pending' => $total - $reviewed,
                'students' => $user->isSupervisor() ? $user->students()->count() : null,
                'weeks' => Logbook::MAX_WEEKS,
                'next_week' => $user->isStudent() ? $user->nextLogbookWeek() : null,
            ],
            'recent' => $recent,
        ]);
    }
}
