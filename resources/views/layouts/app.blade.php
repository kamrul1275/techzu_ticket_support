
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1">

    <title>@yield('title', 'SupportDesk')</title>

    <!-- Bootstrap 5.3 -->
    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <!-- SweetAlert2 CSS -->
    <link
        href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css"
        rel="stylesheet"
    >

    <!-- Custom CSS -->
    <link rel="stylesheet"
          href="{{ asset('css/auth.css') }}">

    <link rel="stylesheet"
          href="{{ asset('css/tickets.css') }}?v=3">

    @stack('styles')
</head>

<body>

@if(request()->routeIs('login', 'register'))

    {{-- Authentication Layout --}}

    <div class="auth-page">
        <div class="auth-container">

            <aside class="auth-brand">

                <div class="brand-logo">
                    <span class="logo-icon">S</span>
                    <span>SupportDesk</span>
                </div>

                <div class="brand-content">

                    <div class="brand-tag">
                        Smart Ticket & SLA Management
                    </div>

                    <h1>Support made simple.</h1>

                    <p>
                        Manage customer requests,
                        track service commitments,
                        and resolve issues efficiently.
                    </p>

                    <div class="brand-points">

                        <div class="brand-point">
                            <span class="brand-point-dot"></span>
                            Track tickets with clear status updates
                        </div>

                        <div class="brand-point">
                            <span class="brand-point-dot"></span>
                            Manage SLA deadlines with confidence
                        </div>

                        <div class="brand-point">
                            <span class="brand-point-dot"></span>
                            Built for customers, agents, and admins
                        </div>

                    </div>

                </div>

                <div class="brand-footer">
                    Modern support experience for efficient teams
                </div>

            </aside>

            <main class="auth-form-panel">

                @yield('content')

            </main>

        </div>
    </div>

@else

    {{-- Application Layout --}}

    <header class="sd-navbar">

        <div class="sd-container sd-navbar-content">

            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="sd-logo">
                <span class="sd-logo-icon">S</span>
                <span>SupportDesk</span>
            </a>

            <!-- Navigation -->
            <nav class="sd-nav">

                <a href="{{ route('dashboard') }}"
                   class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    Dashboard
                </a>

                <a href="{{ route('tickets.index') }}"
                   class="{{ request()->routeIs('tickets.*') ? 'active' : '' }}">
                    Tickets
                </a>

            </nav>

            <!-- User Information -->
            <div class="sd-user">

                <div class="sd-user-info">

                    <span class="sd-user-name">
                        {{ auth()->user()->name }}
                    </span>

                    <span class="sd-user-role">
                        {{ ucfirst(auth()->user()->role) }}
                    </span>

                </div>

                <!-- Logout -->
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

        @yield('content')

    </main>

@endif


<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<!-- Laravel Flash Messages -->
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // Laravel session messages
        const successMessage = {{ \Illuminate\Support\Js::from(session('success')) }};
        const errorMessage = {{ \Illuminate\Support\Js::from(session('error')) }};

        // SweetAlert library check
        if (typeof Swal === 'undefined') {
            console.error('SweetAlert2 could not be loaded.');
            return;
        }

        // Success Alert
        if (successMessage) {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: successMessage,
                confirmButtonText: 'OK',
                confirmButtonColor: '#059669',
                timer: 3000,
                timerProgressBar: true
            });
        }

        // Error Alert
        if (errorMessage) {
            Swal.fire({
                icon: 'error',
                title: 'Something Went Wrong!',
                text: errorMessage,
                confirmButtonText: 'OK',
                confirmButtonColor: '#059669'
            });
        }

    });
</script>

@stack('scripts')

</body>
</html>
