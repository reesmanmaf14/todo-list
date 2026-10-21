<?php

namespace App\Models;

use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'status',
        'due_date',
    ];
    // user_id is deliberately NOT fillable: it is always set from the logged-in user,
    // never from request data (otherwise a user could create tasks for someone else).
    // The same goes for assigned_by, which only TaskController sets when an admin assigns a task

    // Included in the JSON sent to JavaScript, so the page can show the same due-date badge as Blade
    protected $appends = ['due_state'];

    // "overdue" (pending and past its due date), "today" (pending and due today), otherwise null.
    // Worked out on every read, not stored in the database
    protected function dueState(): Attribute
    {
        return Attribute::get(function () {
            if ($this->status === 'Completed' || ! $this->due_date) {
                return null;
            }

            $today = now()->toDateString();

            if ($this->due_date < $today) {
                return 'overdue';
            }

            return $this->due_date === $today ? 'today' : null;
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // The admin who assigned this task (null when the owner created it).
    // Not called assignedBy(): in JSON that name would replace the assigned_by column
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
