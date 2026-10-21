@extends('layouts.app')

@section('content')

@php
    // You cannot demote or delete yourself here (see Admin\UserController)
    $isSelf = $user->is(Auth::user());
@endphp

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <h2 class="mb-0">Edit User</h2>

    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Users
    </a>
</div>

@error('delete')
    <div class="alert alert-danger" role="alert">{{ $message }}</div>
@enderror

<div class="card shadow-sm mb-4">
    <div class="card-body">

        <form action="{{ route('admin.users.update', $user) }}" method="POST">
            @csrf
            @method('PUT')

            @include('admin.users.partials.user-fields', [
                'prefix' => 'editUser',
                'user' => $user,
                'showErrors' => true,
            ])

            <button type="submit" class="btn btn-primary mt-3">
                <i class="bi bi-check-lg" aria-hidden="true"></i> Save Changes
            </button>
        </form>

    </div>
</div>

{{-- Delete --}}
<div class="card shadow-sm danger-zone" id="delete">
    <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-3">
        <div>
            <h3 class="fs-5 mb-1">Delete this user</h3>

            @if($isSelf)
                <p class="text-muted mb-0">You cannot delete your own account here. Use your Profile page instead.</p>
            @else
                <p class="text-muted mb-0">
                    {{ $user->name }} owns {{ $user->tasks_count }} {{ str('task')->plural($user->tasks_count) }}.
                    You can move them to someone else before deleting.
                </p>
            @endif
        </div>

        @unless($isSelf)
            <button type="button"
                    class="btn btn-danger"
                    data-bs-toggle="modal"
                    data-bs-target="#deleteUserModal">
                <i class="bi bi-trash" aria-hidden="true"></i> Delete User
            </button>
        @endunless
    </div>
</div>

@unless($isSelf)
    <div class="modal fade"
         id="deleteUserModal"
         tabindex="-1"
         aria-labelledby="deleteUserModalLabel"
         aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
            <div class="modal-content">
                <form action="{{ route('admin.users.destroy', $user) }}" method="POST">
                    @csrf
                    @method('DELETE')

                    <div class="modal-header">
                        <h2 class="modal-title fs-5" id="deleteUserModalLabel">Delete {{ $user->name }}?</h2>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <p>This account will be removed permanently. This cannot be undone.</p>

                        @if($user->tasks_count > 0)
                            <label class="form-label" for="transfer_to">
                                What should happen to their {{ $user->tasks_count }} {{ str('task')->plural($user->tasks_count) }}?
                            </label>

                            <select id="transfer_to" name="transfer_to" class="form-select">
                                <option value="">Delete their tasks too</option>
                                @foreach($otherUsers as $other)
                                    <option value="{{ $other->id }}" {{ (int) old('transfer_to') === $other->id ? 'selected' : '' }}>
                                        Move to {{ $other->name }} ({{ $other->email }})
                                    </option>
                                @endforeach
                            </select>

                            @error('transfer_to')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                        @else
                            <p class="text-muted mb-0">They have no tasks.</p>
                        @endif
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash" aria-hidden="true"></i> Delete User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- The delete form came back with an error: open the popup again so it can be seen --}}
    @error('transfer_to')
        @push('scripts')
            <script>
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteUserModal')).show();
            </script>
        @endpush
    @enderror

    {{-- Arrived from a Delete button on the Manage Users list (link ends in #delete): open the popup straight away --}}
    @push('scripts')
        <script>
            if (window.location.hash === '#delete') {
                bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteUserModal')).show();
            }
        </script>
    @endpush
@endunless

@endsection
