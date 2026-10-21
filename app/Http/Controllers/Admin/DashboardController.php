<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\View\View;

// The admin dashboard: All Tasks (every user's tasks, with their owner).
// Access is checked by the route ("role:admin"), so only admins get here
class DashboardController extends Controller
{
    public function index(): View
    {
        return view('admin.dashboard', [
            'tasks' => Task::with('user:id,name', 'assigner:id,name')->latest()->get(),
            'users' => User::orderBy('name')->get(['id', 'name']),   // the "Assign to" dropdown
        ]);
    }
}
