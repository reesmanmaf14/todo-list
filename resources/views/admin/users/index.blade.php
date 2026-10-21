@extends('layouts.app')

@section('content')

@php
    // Which popup's form failed validation, so it can be reopened with the errors (null when none did)
    $addUserFailed = old('form_type') === 'create-user' && $errors->any();
    $editUserFailedId = old('form_type') === 'edit-user' && $errors->any() ? (int) old('user_id') : null;
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h2 class="mb-0">Manage Users</h2>

    <div class="d-flex flex-wrap gap-2">
        {{-- Back to the task list (the app's dashboard) --}}
        <a href="{{ route('tasks.index') }}" class="btn btn-secondary">
            <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Tasks
        </a>

        {{-- Opens the Add User popup; without JavaScript the link goes to the Add User page --}}
        <a href="{{ route('admin.users.create') }}"
           class="btn btn-primary"
           data-bs-toggle="modal"
           data-bs-target="#addUserModal">
            <i class="bi bi-person-plus" aria-hidden="true"></i> Add User
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body">

        {{-- Plain GET form: the search and role stay in the URL, so pagination keeps them --}}
        <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end mb-3" role="search">
            <div class="col-12 col-md-6">
                <label class="form-label" for="search">Search</label>
                <input type="search"
                       id="search"
                       name="search"
                       class="form-control"
                       placeholder="Name or email"
                       value="{{ $search }}">
            </div>

            <div class="col-8 col-md-3">
                <label class="form-label" for="role">Role</label>
                <select id="role" name="role" class="form-select">
                    <option value="">All roles</option>
                    @foreach($roles as $roleName)
                        <option value="{{ $roleName }}" {{ $role === $roleName ? 'selected' : '' }}>
                            {{ ucfirst($roleName) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-4 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="bi bi-search" aria-hidden="true"></i> Search
                </button>

                @if($search !== '' || $role)
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary" aria-label="Clear search">
                        <i class="bi bi-x-lg" aria-hidden="true"></i>
                    </a>
                @endif
            </div>
        </form>

        @if($users->isEmpty())
            <p class="text-center text-muted py-4 mb-0">No users match your search.</p>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Tasks</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $listedUser)
                            @php
                                $roleName = $listedUser->roles->first()?->name;
                            @endphp

                            <tr data-user-id="{{ $listedUser->id }}">
                                <td>
                                    {{ $listedUser->name }}

                                    @if($listedUser->is(Auth::user()))
                                        <span class="badge role-you ms-1">You</span>
                                    @endif
                                </td>
                                <td class="text-break">{{ $listedUser->email }}</td>
                                <td>
                                    @if($roleName)
                                        <span class="badge role-{{ $roleName }}">{{ ucfirst($roleName) }}</span>
                                    @else
                                        <span class="text-muted">No role</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">
                                    {{ $listedUser->pending_tasks_count }} pending
                                    <span class="text-muted">·</span>
                                    {{ $listedUser->completed_tasks_count }} completed
                                </td>
                                <td class="text-nowrap">{{ $listedUser->created_at?->format('Y-m-d') }}</td>
                                <td>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="button"
                                                class="btn btn-sm btn-primary"
                                                data-bs-toggle="modal"
                                                data-bs-target="#editUserModal-{{ $listedUser->id }}">
                                            <i class="bi bi-pencil" aria-hidden="true"></i> Edit
                                        </button>

                                        {{-- Deleting needs more choices (who gets their tasks), so it opens on the Edit User page --}}
                                        @unless($listedUser->is(Auth::user()))
                                            <a href="{{ route('admin.users.edit', $listedUser) }}#delete"
                                               class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash" aria-hidden="true"></i> Delete
                                            </a>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($users->hasPages())
                <div class="mt-3">
                    {{ $users->links('pagination::bootstrap-5') }}
                </div>
            @endif
        @endif

    </div>
</div>

{{-- Add User popup (same fields and route as the Add User page) --}}
<div class="modal fade"
     id="addUserModal"
     tabindex="-1"
     aria-labelledby="addUserModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">

            <form action="{{ route('admin.users.store') }}" method="POST">
                @csrf

                <input type="hidden" name="form_type" value="create-user">

                <div class="modal-header">
                    <h2 class="modal-title fs-5" id="addUserModalLabel">Add User</h2>

                    <button type="button"
                            class="btn-close"
                            data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @include('admin.users.partials.user-fields', [
                        'prefix' => 'addUser',
                        'user' => null,
                        'showErrors' => $addUserFailed,
                    ])
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        Cancel
                    </button>

                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-person-plus" aria-hidden="true"></i> Add User
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

{{-- One Edit popup per user on this page --}}
@foreach($users as $listedUser)
    @include('admin.users.partials.edit-modal', [
        'user' => $listedUser,
        'editFailed' => $editUserFailedId === $listedUser->id,
    ])
@endforeach

{{-- A form came back with validation errors: reopen its popup so the messages are visible --}}
@if($addUserFailed || $editUserFailedId)
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const modal = document.getElementById(@json($addUserFailed ? 'addUserModal' : 'editUserModal-'.$editUserFailedId));

                if (modal) {
                    bootstrap.Modal.getOrCreateInstance(modal).show();
                }
            });
        </script>
    @endpush
@endif

@endsection
