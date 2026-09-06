<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $sessions = $request->user()->exportSessions();

        return view('dashboard', [
            'stats' => [
                'total' => (clone $sessions)->count(),
                'pending' => (clone $sessions)->inProgress()->count(),
                'approved' => (clone $sessions)->completed()->get()->filter->passed()->count(),
            ],
            'activities' => $request->user()
                ->exportSessions()
                ->latest('updated_at')
                ->take(5)
                ->get()
                ->map(fn ($session) => [
                    'session' => $session,
                    'batch' => $session->material,
                    'destination' => $session->category_name,
                    'date' => $session->updated_at->format('M j, Y'),
                    'status' => match (true) {
                        ! $session->isCompleted() => 'In Progress',
                        $session->passed() => 'Candidate Found',
                        default => 'Needs Review',
                    },
                    'tone' => match (true) {
                        ! $session->isCompleted() => 'warning',
                        $session->passed() => 'success',
                        default => 'danger',
                    },
                ]),
        ]);
    }
}
