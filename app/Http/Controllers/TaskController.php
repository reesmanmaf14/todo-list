<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;


class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
         $tasks = Task::latest()->get();

    return view('tasks.index', compact('tasks'));  //compact()sends variable to the view
    }

    
    public function store(Request $request)
{
    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'due_date' => 'nullable|date|after_or_equal:today',
    ]);

    $validated['status'] = 'Pending';

    $task = Task::create($validated); //insert the validated data into the table

  

    if ($request->expectsJson()) {
        return response()->json([  
            'message' => 'Task created successfully!',
            'task' => $task,
            'created_date' => $task->created_at->format('Y-m-d'), 
            'urls' => [
                'update' => route('tasks.update', $task),
                'destroy' => route('tasks.destroy', $task),
            ],
        ], 201);  
    }

    return redirect()
        ->route('tasks.index')
        ->with('success', 'Task created successfully!');
}

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Task $task)
{
    // Keeping an existing (possibly overdue) due date is allowed;
    // only a newly chosen date must be today or later
    $dueDateRules = 'nullable|date';   //date validation rules

    if ($request->input('due_date') !== $task->due_date) {
        $dueDateRules .= '|after_or_equal:today';   //another validation rule
    }

    $validated = $request->validate([
        'title' => 'required|string|max:255',
        'description' => 'nullable|string',
        'due_date' => $dueDateRules,
        'status' => 'required|in:Pending,Completed',
    ]);

    $task->update($validated);

    // AJAX requests (edit modal and Complete/Mark Pending button) get the updated task as JSON
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
}
