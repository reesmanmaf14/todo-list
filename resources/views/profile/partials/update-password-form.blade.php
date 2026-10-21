<section>
    <h3 class="fs-5">
        <i class="bi bi-key" aria-hidden="true"></i> Update Password
    </h3>

    <p class="text-muted small">
        Ensure your account is using a long, random password to stay secure.
    </p>

    {{-- Errors for this form are kept separately (in the "updatePassword" error bag) --}}
    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="mb-3">
            <label class="form-label" for="update_password_current_password">Current Password</label>

            <input type="password"
                   id="update_password_current_password"
                   name="current_password"
                   class="form-control"
                   autocomplete="current-password">

            @error('current_password', 'updatePassword')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="update_password_password">New Password</label>

            <input type="password"
                   id="update_password_password"
                   name="password"
                   class="form-control"
                   autocomplete="new-password">

            @error('password', 'updatePassword')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="update_password_password_confirmation">Confirm Password</label>

            <input type="password"
                   id="update_password_password_confirmation"
                   name="password_confirmation"
                   class="form-control"
                   autocomplete="new-password">

            @error('password_confirmation', 'updatePassword')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg" aria-hidden="true"></i> Save
            </button>

            @if (session('status') === 'password-updated')
                <span class="text-success small">
                    <i class="bi bi-check-circle" aria-hidden="true"></i> Saved.
                </span>
            @endif
        </div>
    </form>
</section>
