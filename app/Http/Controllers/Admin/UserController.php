<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

// Manage Users pages for admins. Access is checked by the route ("can:manage users"), so only admins get here.
// An admin can never remove their own admin role or delete their own account here, so there is always at least one admin.
class UserController extends Controller
{
    /**
     * List every user, with search, a role filter and task counts.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search'));
        $role = $request->query('role');
        $roles = Role::orderBy('name')->pluck('name');

        $users = User::query()
            ->with('roles')
            ->withCount([
                'tasks as pending_tasks_count' => fn ($query) => $query->where('status', 'Pending'),
                'tasks as completed_tasks_count' => fn ($query) => $query->where('status', 'Completed'),
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            // Unknown roles in the URL are ignored instead of causing an error
            ->when($roles->contains($role), fn ($query) => $query->role($role))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', compact('users', 'roles', 'search', 'role'));
    }

    /**
     * Show the Add User form.
     */
    public function create(): View
    {
        // The roles the admin can choose from (admin, user), straight from Spatie's roles table
        $roles = Role::orderBy('name')->pluck('name');

        return view('admin.users.create', compact('roles'));
    }

    /**
     * Create the account and give it the chosen role.
     */
    public function store(Request $request): RedirectResponse
    {
        // Same rules as self-registration, plus a role that must exist in the roles table
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $user->assignRole($request->role);

        // Back to the list (users are added from its Add User popup), where the new user now appears
        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} was created as {$request->role}.");
    }

    /**
     * Show the Edit User form (details, role, new password, and the Delete button).
     */
    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->pluck('name');

        $user->loadCount('tasks');

        // People who can take over this user's tasks before the account is deleted
        $otherUsers = User::whereKeyNot($user->id)->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.users.edit', compact('user', 'roles', 'otherUsers'));
    }

    /**
     * Save name, email and role, and the new password if one was typed.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            // Optional: leave both password fields empty to keep the current password
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'exists:roles,name'],
        ]);

        // Taking away your own admin role would lock you out of this page
        if ($user->is($request->user()) && $validated['role'] !== 'admin') {
            return back()
                ->withInput()
                ->withErrors(['role' => 'You cannot remove your own admin role. Ask another admin to do it.']);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();
        $user->syncRoles([$validated['role']]);

        return redirect()
            ->route('admin.users.index')
            ->with('success', "User {$user->name} was updated.");
    }

    /**
     * Delete the account. Its tasks are deleted too, unless the admin chose someone to take them over.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        // Use the Profile page to delete your own account
        if ($user->is($request->user())) {
            return redirect()
                ->route('admin.users.edit', $user)
                ->withErrors(['delete' => 'You cannot delete your own account from here.']);
        }

        $validated = $request->validate([
            'transfer_to' => ['nullable', 'integer', Rule::exists('users', 'id'), Rule::notIn([$user->id])],
        ]);

        $receiver = isset($validated['transfer_to']) ? User::find($validated['transfer_to']) : null;

        // Both steps succeed together, so tasks are never lost halfway
        $movedCount = DB::transaction(function () use ($user, $receiver) {
            $moved = $receiver
                ? Task::where('user_id', $user->id)->update(['user_id' => $receiver->id])
                : 0;

            // tasks.user_id is cascadeOnDelete: any tasks still owned by this user are deleted with the account
            $user->delete();

            return $moved;
        });

        $message = $receiver
            ? "User {$user->name} was deleted. {$movedCount} ".str('task')->plural($movedCount)." moved to {$receiver->name}."
            : "User {$user->name} and their tasks were deleted.";

        return redirect()
            ->route('admin.users.index')
            ->with('success', $message);
    }
}
