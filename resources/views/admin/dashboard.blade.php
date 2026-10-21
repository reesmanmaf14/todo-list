{{-- The admin dashboard: totals across all users + quick links (the task list itself is on the Tasks page) --}}
@extends('layouts.app')

@section('content')

<h2 class="mb-4">Admin Dashboard</h2>

@include('tasks.partials.task-counts', ['userCount' => $users->count()])

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-list-check" aria-hidden="true"></i> Task Management</div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="{{ route('tasks.index') }}" class="btn btn-primary">All Tasks</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm h-100">
            <div class="card-header"><i class="bi bi-people" aria-hidden="true"></i> User Management</div>
            <div class="card-body d-flex flex-wrap gap-2">
                <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><i class="bi bi-person-plus" aria-hidden="true"></i> Add User</a>
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">All Users</a>
            </div>
        </div>
    </div>
</div>

@endsection
