<x-guest-layout>
    <div class="mb-4 text-sm text-crema-muted">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-sm text-crema-roast bg-crema-latte-tint border-l-[3px] border-crema-espresso rounded-md px-3 py-2">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Resend Verification Email') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="underline underline-offset-4 decoration-crema-latte text-sm text-crema-muted hover:text-crema-espresso hover:decoration-crema-espresso rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-150 focus:ring-crema-espresso">
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</x-guest-layout>
