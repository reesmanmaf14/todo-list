<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    // Edit and delete: the owner, unless an admin assigned the task to them; admins ("manage all tasks") always.
    // Everyone else gets a 403
    public function update(User $user, Task $task): bool
    {
        return ($user->id === $task->user_id && $task->assigned_by === null) || $user->can('manage all tasks');
    }

    public function delete(User $user, Task $task): bool
    {
        return ($user->id === $task->user_id && $task->assigned_by === null) || $user->can('manage all tasks');
    }

    // Complete / Mark Pending: the owner (including tasks assigned to them) and admins
    public function updateStatus(User $user, Task $task): bool
    {
        return $user->id === $task->user_id || $user->can('manage all tasks');
    }
}
