<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Admins ("view all tasks") see every task with its owner's name; everyone else only their own.
        // assigner: the name shown as "Assigned by ..." on assigned tasks
        $tasks = $request->user()->can('view all tasks')
            ? Task::with('user:id,name', 'assigner:id,name')->latest()->get()
            : $request->user()->tasks()->with('assigner:id,name')->latest()->get();

        $users = $this->usersForAssigning($request->user());

    return view('tasks.index', compact('tasks', 'users'));
    }


    public function store(Request $request)
{
    $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'due_date' => 'nullable|date|after_or_equal:today',
    ];

    // Only admins ("assign tasks") may choose the owner; for everyone else user_id is ignored
    if ($request->user()->can('assign tasks')) {
        $rules['user_id'] = 'nullable|exists:users,id';
    }

    $validated = $request->validate($rules);

    $validated['status'] = 'Pending';

    $owner = isset($validated['user_id']) ? User::find($validated['user_id']) : $request->user();

    $task = $owner->tasks()->make($validated);

    // Created by an admin for someone else: remember who assigned it (the owner may then only change its status)
    if ($owner->isNot($request->user())) {
        $task->assigned_by = $request->user()->id;
    }

    $task->save();
    $task->load('user:id,name', 'assigner:id,name');



    if ($request->expectsJson()) {
        $users = $this->usersForAssigning($request->user());

        return response()->json([
            'message' => 'Task created successfully!',
            'task' => $task,
            // The new task's table row and Edit popup, so JavaScript can add them without a page reload
            'row' => view('tasks.partials.task-row', ['task' => $task])->render(),
            'modal' => view('tasks.partials.edit-modal', ['task' => $task, 'editFailed' => false, 'users' => $users])->render(),
        ], 201);
    }

    return redirect()
        ->route('tasks.index')
        ->with('success', 'Task created successfully!');
}

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
{
    Gate::authorize('update', $task); //TaskPolicy: 403 if this task belongs to someone else

    // Keeping an existing (possibly overdue) due date is allowed;
    // only a newly chosen date must be today or later
    $dueDateRules = 'nullable|date';

    if ($request->input('due_date') !== $task->due_date) {
        $dueDateRules .= '|after_or_equal:today';
    }

    $rules = [
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'due_date' => $dueDateRules,
        'status' => 'required|in:Pending,Completed',
    ];

    // Only admins ("assign tasks") may change the owner; for everyone else user_id is ignored
    if ($request->user()->can('assign tasks')) {
        $rules['user_id'] = 'nullable|exists:users,id';
    }

    $validated = $request->validate($rules);

    // An admin moved the task to another owner (the Edit form always sends the current owner, so compare first)
    if (isset($validated['user_id']) && (int) $validated['user_id'] !== (int) $task->user_id) {
        $task->user_id = (int) $validated['user_id'];

        // Moved to someone else: they may only change its status. Moved to yourself: a normal task again
        $task->assigned_by = $task->user_id === $request->user()->id ? null : $request->user()->id;
    }

    $task->update($validated);
    $task->load('user:id,name', 'assigner:id,name');

    // AJAX requests from the edit modal get the updated task as JSON
    if ($request->expectsJson()) {
        return response()->json([
            'message' => 'Task updated successfully!',
            'task' => $task,
        ]);
    }

    return redirect()
        ->route('tasks.index')
        ->with('success', 'Task updated successfully!');
}

    /**
     * Complete / Mark Pending: change only the status.
     */
    public function updateStatus(Request $request, Task $task)
{
    Gate::authorize('updateStatus', $task); //TaskPolicy: the owner (also of assigned tasks) or an admin

    // Only the status is accepted, so this button can't be used to change the title, dates or owner
    $validated = $request->validate([
        'status' => 'required|in:Pending,Completed',
    ]);

    $task->update($validated);
    $task->load('user:id,name', 'assigner:id,name');

    if ($request->expectsJson()) {
        return response()->json([
            'message' => 'Task updated successfully!',
            'task' => $task,
        ]);
    }

    return redirect()
        ->route('tasks.index')
        ->with('success', 'Task updated successfully!');
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Task $task)
{
    Gate::authorize('delete', $task); //TaskPolicy: 403 if this task belongs to someone else

    $task->delete();

    // AJAX requests only need the message; JavaScript removes the row itself
    if ($request->expectsJson()) {
        return response()->json([
            'message' => 'Task deleted successfully!',
        ]);
    }

    return redirect()
        ->route('tasks.index')
        ->with('success', 'Task deleted successfully!');
}

    // Users shown in the "Assign to" dropdown: only admins ("assign tasks") get the list
    private function usersForAssigning(User $user)
    {
        return $user->can('assign tasks') ? User::orderBy('name')->get(['id', 'name']) : collect();
    }
}
