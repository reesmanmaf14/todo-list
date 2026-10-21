<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Full-width Register button -->
        <x-primary-button class="w-full mt-6">
            {{ __('Create account') }}
        </x-primary-button>
    </form>

    <!-- Link back to the login page, for people who already have an account -->
    <p class="mt-6 pt-5 border-t border-crema-latte text-center text-sm text-crema-muted">
        {{ __('Already have an account?') }}
        <a class="font-medium text-crema-espresso underline underline-offset-4 decoration-crema-latte hover:text-crema-espresso-hover hover:decoration-crema-espresso rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 transition duration-150 focus:ring-crema-espresso" href="{{ route('login') }}">
            {{ __('Log in') }}
        </a>
    </p>
</x-guest-layout>
