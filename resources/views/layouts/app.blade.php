<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Standard Laravel CSRF meta tag (read below and sent with every Axios request) --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>My To-Do List</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >
</head>
<body class="bg-light">

    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="{{ route('tasks.index') }}">
                My To-Do List
            </a>
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
    </script>

    @stack('scripts')

</body>
</html>