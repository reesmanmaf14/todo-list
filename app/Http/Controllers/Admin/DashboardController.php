<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\View\View;

// The admin's home page after login: every task, Add Task (with Assign to) and totals across all users.
// Access is checked by the route ("role:admin"), so only admins get here
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'tasks' => Task::with('user:id,name', 'assigner:id,name')->latest()->get(),
            // Every user: fills the "Assign to" dropdown and gives the Users count
            'users' => User::orderBy('name')->get(['id', 'name']),
        ]);
    }
}
