@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

    <!-- Welcome Section -->
    <section class="sd-welcome">

        <div>
            <span class="sd-welcome-label">
                SUPPORT MANAGEMENT
            </span>

            <h1>
                Welcome back, {{ auth()->user()->name }}!
            </h1>

            <p>
                Manage your support tickets, track progress,
                and stay updated from one place.
            </p>
        </div>

        <span class="sd-welcome-icon">
            ✓
        </span>

    </section>

    <!-- Section Heading -->
    <div class="sd-section-heading">
        <div>
            <h2>Quick Actions</h2>
            <p>Choose what you would like to do.</p>
        </div>
    </div>

    <!-- Quick Action Cards -->
    <section class="sd-card-grid">

        <!-- Ticket List -->
        <article class="sd-action-card">

            <div class="sd-card-icon">
                ☷
            </div>

            <h3>Support Tickets</h3>

            <p>
                View and track your support requests
                and their current status.
            </p>

            <a href="{{ route('tickets.index') }}"
               class="sd-card-link">
                View Tickets →
            </a>

        </article>

        <!-- Create Ticket -->
        @can('create', App\Models\Ticket::class)

            <article class="sd-action-card">

                <div class="sd-card-icon">
                    +
                </div>

                <h3>Create Ticket</h3>

                <p>
                    Submit a new support request
                    and let our team assist you.
                </p>

                <a href="{{ route('tickets.create') }}"
                   class="sd-card-link">
                    Create New Ticket →
                </a>

            </article>

        @endcan

        <!-- Account Information -->
        <article class="sd-action-card">

            <div class="sd-card-icon">
                ◉
            </div>

            <h3>My Account</h3>

            <p>
                View your account information
                and assigned role.
            </p>

            <span class="sd-role-badge">
                {{ ucfirst(auth()->user()->role) }}
            </span>

        </article>

    </section>

    <!-- Account Overview -->
    <section class="sd-account-card">

        <div class="sd-section-heading">
            <div>
                <h2>Account Overview</h2>
                <p>Your current account details.</p>
            </div>

            <span class="sd-active-badge">
                Active Session
            </span>
        </div>

        <div class="sd-account-grid">

            <div class="sd-info-item">
                <span>Full Name</span>
                <strong>{{ auth()->user()->name }}</strong>
            </div>

            <div class="sd-info-item">
                <span>Email Address</span>
                <strong>{{ auth()->user()->email }}</strong>
            </div>

            <div class="sd-info-item">
                <span>Account Role</span>
                <strong>
                    {{ ucfirst(auth()->user()->role) }}
                </strong>
            </div>

            <div class="sd-info-item">
                <span>Member Since</span>
                <strong>
                    {{ auth()->user()->created_at?->format('d M Y') ?? 'N/A' }}
                </strong>
            </div>

        </div>

    </section>

@endsection