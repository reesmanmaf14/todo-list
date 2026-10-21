<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

// A regular user's dashboard: My Tasks (only their own tasks).
// Admins have their own one (Admin\DashboardController); /dashboard sends each role to the right one
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard', [
            // assigner: the name shown as "Assigned by ..." on tasks an admin gave them
            'tasks' => $request->user()->tasks()->with('assigner:id,name')->latest()->get(),
            'users' => collect(),   // regular users can't assign tasks, so no "Assign to" list
        ]);
    }
}
