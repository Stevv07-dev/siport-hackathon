<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class ExportHistoryController extends Controller
{
    /**
     * Every screening session the user has ever started — in progress or
     * completed — so they can resume an unfinished one or revisit a verdict
     * from long ago, even after signing out and back in.
     */
    public function index(Request $request): View
    {
        $sessions = $request->user()
            ->exportSessions()
            ->latest('updated_at')
            ->get();

        return view('history.index', ['sessions' => $sessions]);
    }
}
