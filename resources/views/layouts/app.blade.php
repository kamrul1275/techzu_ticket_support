<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title', 'Dashboard') | SupportDesk</title>

    <!-- Shared Theme -->
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">

    <!-- Dashboard & Ticket Styles -->
    <link rel="stylesheet" href="{{ asset('css/tickets.css') }}?v=2">
</head>

<body class="sd-body">

    <!-- Navbar -->
    <header class="sd-navbar">
        <div class="sd-container sd-navbar-content">

            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="sd-logo">
                <span class="sd-logo-icon">S</span>
                <span>SupportDesk</span>
            </a>

            <!-- Navigation -->
            <nav class="sd-nav" aria-label="Main navigation">

                <a href="{{ route('dashboard') }}"
                   class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    Dashboard
                </a>

                <a href="{{ route('tickets.index') }}"
                   class="{{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                    Tickets
                </a>

            </nav>

            <!-- User -->
            <div class="sd-user">

                <div class="sd-user-info">
                    <span class="sd-user-name">
                        {{ auth()->user()->name }}
                    </span>

                    <span class="sd-user-role">
                        {{ ucfirst(auth()->user()->role) }}
                    </span>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <button type="submit" class="sd-logout">
                        Logout
                    </button>
                </form>

            </div>

        </div>
    </header>

    <!-- Main Content -->
    <main class="sd-main sd-container">

        @if(session('success'))
            <div class="sd-alert-success" role="status">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="sd-alert-error" role="alert">
                {{ session('error') }}
            </div>
        @endif

        @yield('content')

    </main>

</body>
</html>