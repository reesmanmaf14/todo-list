<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Standard Laravel CSRF meta tag (read below and sent with every Axios request) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Crema') }}</title>

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    {{-- Icons used as <i class="bi bi-..."></i> --}}
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    {{-- Warm Ivory & Espresso theme on top of Bootstrap (must load after it) --}}
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">

    @stack('styles')
</head>
<body class="app-theme">

    <nav class="navbar navbar-light navbar-theme">
        <div class="container">
            {{-- Logo = home: route('dashboard') opens the admin or user dashboard by role --}}
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <x-application-logo class="brand-logo" />
                <span class="brand-name">{{ config('app.name', 'Crema') }}</span>
            </a>

            <div class="d-flex align-items-center gap-2">
                @auth
                    {{-- Admins only; the pages themselves are also protected on the server.
                         Solid white while you are on any Manage Users page --}}
                    @can('manage users')
                        <a class="btn btn-sm {{ request()->routeIs('admin.users.*') ? 'btn-light' : 'btn-outline-light' }}"
                           href="{{ route('admin.users.index') }}">
                            <i class="bi bi-people" aria-hidden="true"></i> Manage Users
                        </a>
                    @endcan

                    {{-- Account menu: Bootstrap dropdown (bootstrap.bundle.min.js, loaded below) --}}
                    <div class="dropdown">
                        <button type="button"
                                class="btn btn-outline-light btn-sm rounded-circle"
                                data-bs-toggle="dropdown"
                                aria-expanded="false"
                                aria-label="Account menu">
                            <i class="bi bi-person-fill" aria-hidden="true"></i>
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li>
                                <div class="dropdown-header">
                                    <div class="fw-semibold text-body">{{ Auth::user()->name }}</div>
                                    <div class="small">{{ Auth::user()->email }}</div>
                                </div>
                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <li>
                                <a class="dropdown-item" href="{{ route('profile.edit') }}">
                                    <i class="bi bi-gear" aria-hidden="true"></i> Profile
                                </a>
                            </li>

                            <li><hr class="dropdown-divider"></li>

                            <li>
                                {{-- Logout must be a POST with CSRF, so a link/img on another site can't log users out --}}
                                <form method="POST" action="{{ route('logout') }}" class="mb-0">
                                    @csrf
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-box-arrow-right" aria-hidden="true"></i> Log out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                @else
                    <a class="btn btn-outline-light btn-sm" href="{{ route('login') }}">Log in</a>
                    <a class="btn btn-light btn-sm" href="{{ route('register') }}">Register</a>
                @endauth
            </div>
        </div>
    </nav>

    <div class="container py-4">
        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    {{-- Axios sends the task requests to Laravel without reloading the page --}}
    <script src="https://cdn.jsdelivr.net/npm/axios@1.7.7/dist/axios.min.js"
            integrity="sha384-BSfV8fZD1qtU+7KBLfwU6gmcvPJpmwSoXe28kfEv6tS2XTcI4RYmu9GIrKLrhA4y"
            crossorigin="anonymous"></script>

    <script>
        // Send the CSRF token with every Axios request, so Laravel trusts it (otherwise: 419 error)
        axios.defaults.headers.common['X-CSRF-TOKEN'] =
            document.querySelector('meta[name="csrf-token"]').content;

        // When any popup opens, move the keyboard focus into it: to the element marked data-autofocus
        // (e.g. Cancel in "Are you sure?" popups), otherwise to the first field
        document.addEventListener('shown.bs.modal', function (event) {
            const target = event.target.querySelector('[data-autofocus]')
                || event.target.querySelector('.modal-body input:not([type="hidden"]):not([disabled]), .modal-body select, .modal-body textarea');

            if (target) {
                target.focus();
            }
        });
    </script>

    @stack('scripts')

</body>
</html>