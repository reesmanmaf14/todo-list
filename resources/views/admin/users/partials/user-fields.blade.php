{{-- Name, email, role and password fields, shared by the Add User page, the Add User popup and the Edit User popups.
     $prefix:     makes the field ids unique when several forms are on one page (e.g. "addUser", "editUser-5")
     $user:       the user being edited, or null when adding a new one
     $roles:      the role names to choose from
     $showErrors: true when this form was the one submitted (only then are old input and errors shown) --}}
@php
    $isEdit = $user !== null;
    $isSelf = $isEdit && $user->is(Auth::user());

    // After a failed submission show what was typed; otherwise the saved values
    $nameValue = $showErrors ? old('name', $user?->name) : $user?->name;
    $emailValue = $showErrors ? old('email', $user?->email) : $user?->email;
    $roleValue = $showErrors ? old('role', $user?->roles->first()?->name ?? 'user') : ($user?->roles->first()?->name ?? 'user');
@endphp

<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}Name">Name</label>

    <input type="text"
           id="{{ $prefix }}Name"
           name="name"
           class="form-control @if($showErrors) @error('name') is-invalid @enderror @endif"
           value="{{ $nameValue }}"
           autocomplete="off">

    @if($showErrors)
        @error('name')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    @endif
</div>

<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}Email">Email</label>

    <input type="email"
           id="{{ $prefix }}Email"
           name="email"
           class="form-control @if($showErrors) @error('email') is-invalid @enderror @endif"
           value="{{ $emailValue }}"
           autocomplete="off">

    @if($showErrors)
        @error('email')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    @endif
</div>

<div class="mb-3">
    <label class="form-label" for="{{ $prefix }}Role">Role</label>

    <select id="{{ $prefix }}Role" name="role" class="form-select @if($showErrors) @error('role') is-invalid @enderror @endif">
        @foreach($roles as $role)
            <option value="{{ $role }}" {{ $roleValue === $role ? 'selected' : '' }}>
                {{ ucfirst($role) }}
            </option>
        @endforeach
    </select>

    @if($isSelf)
        <div class="form-text">You cannot remove your own admin role.</div>
    @endif

    @if($showErrors)
        @error('role')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    @endif
</div>

{{-- Passwords are never refilled after a validation error --}}
<fieldset class="mb-1">
    @if($isEdit)
        <legend class="fs-6 fw-semibold mb-1">Reset password</legend>
        <p class="form-text mt-0">Leave both fields empty to keep the current password.</p>
    @endif

    <div class="row g-2">
        <div class="col-12 col-md-6">
            <label class="form-label" for="{{ $prefix }}Password">{{ $isEdit ? 'New password' : 'Password' }}</label>

            <input type="password"
                   id="{{ $prefix }}Password"
                   name="password"
                   class="form-control @if($showErrors) @error('password') is-invalid @enderror @endif"
                   autocomplete="new-password">

            @if($showErrors)
                @error('password')
                    <div class="text-danger small mt-1">{{ $message }}</div>
                @enderror
            @endif
        </div>

        <div class="col-12 col-md-6">
            <label class="form-label" for="{{ $prefix }}PasswordConfirmation">{{ $isEdit ? 'Confirm new password' : 'Confirm password' }}</label>

            <input type="password"
                   id="{{ $prefix }}PasswordConfirmation"
                   name="password_confirmation"
                   class="form-control"
                   autocomplete="new-password">
        </div>
    </div>
</fieldset>
