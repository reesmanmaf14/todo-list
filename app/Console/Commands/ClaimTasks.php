<?php

namespace App\Console\Commands;

use App\Models\Task;
use App\Models\User;
use Illuminate\Console\Command;

// One-off: give the tasks created before login existed (user_id = NULL) to a user
class ClaimTasks extends Command
{
    protected $signature = 'tasks:claim {email : Email of the user who will own the tasks}';

    protected $description = 'Assign all tasks without an owner to the given user';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user found with that email. Register the account first.');

            return self::FAILURE;
        }

        $count = Task::whereNull('user_id')->update(['user_id' => $user->id]);

        $this->info("Assigned {$count} task(s) to {$user->email}.");

        return self::SUCCESS;
    }
}
