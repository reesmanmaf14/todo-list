@extends('layouts.app')

@section('content')

{{-- Full-page version of the Add User popup on the Manage Users list (same fields, same route) --}}
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h2 class="mb-0">Add User</h2>

    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Users
    </a>
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

<div class="card shadow-sm">
    <div class="card-body">

        <form action="{{ route('admin.users.store') }}" method="POST">
            @csrf

            <input type="hidden" name="form_type" value="create-user">

            @include('admin.users.partials.user-fields', [
                'prefix' => 'addUser',
                'user' => null,
                'showErrors' => true,
            ])

            <button type="submit" class="btn btn-primary mt-3">
                <i class="bi bi-person-plus" aria-hidden="true"></i> Add User
            </button>
        </form>

    </div>
</div>

@endsection
