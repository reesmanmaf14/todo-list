<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

// The only way to become an admin: run this on the server for an account that already registered
class MakeAdmin extends Command
{
    protected $signature = 'users:make-admin {email : Email of the registered user to promote}';

    protected $description = 'Give an existing user the admin role';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('No user found with that email. Register the account first.');

            return self::FAILURE;
        }

        if (! Role::where('name', 'admin')->exists()) {
            $this->error('The admin role does not exist yet. Run: php artisan db:seed --class=RolesAndPermissionsSeeder');

            return self::FAILURE;
        }

        $user->assignRole('admin');

        $this->info("{$user->email} is now an admin.");

        return self::SUCCESS;
    }
}
