<section>
    <h3 class="fs-5">
        <i class="bi bi-person-circle" aria-hidden="true"></i> Profile Information
    </h3>

    <p class="text-muted small">
        Update your account's profile information and email address.
    </p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label class="form-label" for="name">Name</label>

            <input type="text"
                   id="name"
                   name="name"
                   class="form-control"
                   value="{{ old('name', $user->name) }}"
                   required
                   autofocus
                   autocomplete="name">

            @error('name')
                <div class="text-danger">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label class="form-label" for="email">Email</label>

            <input type="email"
                   id="email"
                   name="email"
                   class="form-control"
                   value="{{ old('email', $user->email) }}"
                   required
                   autocomplete="username">

            @error('email')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <p class="small mt-2 mb-0">
                    Your email address is unverified.

                    <button form="send-verification" class="btn btn-link btn-sm p-0 align-baseline">
                        Click here to re-send the verification email.
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                    <p class="small text-success mt-2 mb-0">
                        A new verification link has been sent to your email address.
                    </p>
                @endif
            @endif
        </div>

        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-check-lg" aria-hidden="true"></i> Save
            </button>

            @if (session('status') === 'profile-updated')
                <span class="text-success small">
                    <i class="bi bi-check-circle" aria-hidden="true"></i> Saved.
                </span>
            @endif
        </div>
    </form>
</section>
