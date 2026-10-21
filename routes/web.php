<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

#Home = your dashboard (guests are sent on to the login page by /dashboard)
Route::get('/', function () {
    return redirect()->route('dashboard');
});

#Breeze still sends people here (register, email verification, password confirm) - pass each role on to its own dashboard.
#reflash: keeps a "Task created successfully!" message alive through this extra redirect
Route::get('/dashboard', function () {
    session()->reflash();

    return auth()->user()->hasRole('admin')
        ? redirect()->route('admin.dashboard')
        : redirect()->route('user.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

#My Tasks: a regular user's own tasks
Route::get('/user/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('user.dashboard');

#All Tasks: every user's tasks. role:admin checks the role itself, so a regular user gets a 403 even if they type the URL
Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])
    ->middleware(['auth', 'role:admin'])->name('admin.dashboard');

#auth: guests are redirected to the login page (AJAX requests get a 401 instead)
Route::middleware('auth')->group(function () {
    #No separate task page any more: /tasks (where task forms redirect after saving) goes to your dashboard
    Route::get('/tasks', function () {
        session()->reflash();

        return redirect()->route('dashboard');
    })->name('tasks.index');

    Route::resource('tasks', TaskController::class)
        ->except(['index', 'show', 'create', 'edit']); #add/edit forms are popups on the dashboard

    #Complete / Mark Pending button: changes only the status (allowed on tasks an admin assigned to you)
    Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');

    #Admins only ("manage users"): everyone else gets a 403, even if they type the URL or send the form themselves
    Route::middleware('can:manage users')->group(function () {
        Route::get('/admin/users', [UserController::class, 'index'])->name('admin.users.index');
        Route::get('/admin/users/create', [UserController::class, 'create'])->name('admin.users.create');
        Route::post('/admin/users', [UserController::class, 'store'])->name('admin.users.store');
        Route::get('/admin/users/{user}/edit', [UserController::class, 'edit'])->name('admin.users.edit');
        Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('admin.users.update');
        Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('admin.users.destroy');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
