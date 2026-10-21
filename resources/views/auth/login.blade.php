<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me and Forgot password, on one row under the password field -->
        <div class="flex flex-wrap items-center justify-between gap-2 mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-crema-latte text-crema-espresso focus:ring-crema-espresso/25" name="remember">
                <span class="ms-2 text-sm text-crema-muted">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="underline underline-offset-4 decoration-crema-latte text-sm text-crema-muted hover:text-crema-espresso hover:decoration-crema-espresso rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-150 focus:ring-crema-espresso" href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif
        </div>

        <!-- Full-width Log in button -->
        <x-primary-button class="w-full mt-6">
            {{ __('Log in') }}
        </x-primary-button>
    </form>

    <!-- Link to the register page, for people without an account -->
    @if (Route::has('register'))
        <p class="mt-6 pt-5 border-t border-crema-latte text-center text-sm text-crema-muted">
            {{ __("Don't have an account?") }}
            <a class="font-medium text-crema-espresso underline underline-offset-4 decoration-crema-latte hover:text-crema-espresso-hover hover:decoration-crema-espresso rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-150 focus:ring-crema-espresso" href="{{ route('register') }}">
                {{ __('Create one') }}
            </a>
        </p>
    @endif
</x-guest-layout>
