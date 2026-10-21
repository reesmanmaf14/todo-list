<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

// A regular user's home page after login: their own tasks, Add Task and their counts.
// Admins have their own page (Admin\DashboardController); /dashboard sends each role to the right one
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard', [
            // Same list as the task page; assigner: the name shown as "Assigned by ..." on assigned tasks
            'tasks' => $request->user()->tasks()->with('assigner:id,name')->latest()->get(),
            'users' => collect(),   // regular users can't assign tasks, so no "Assign to" list
        ]);
    }
}
