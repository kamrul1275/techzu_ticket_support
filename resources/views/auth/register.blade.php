@extends('layouts.app')

@section('title', 'Register | SupportDesk')

@section('content')

    <div class="form-heading">
        <h2>Create your account</h2>

        <p>
            Join SupportDesk to submit and track
            your support requests.
        </p>
    </div>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div class="form-group">
            <label for="name" class="form-label">
                Full Name
            </label>

            <input
                type="text"
                id="name"
                name="name"
                class="form-control @error('name') is-invalid @enderror"
                value="{{ old('name') }}"
                placeholder="Enter your full name"
                autocomplete="name"
                required
            >

            @error('name')
                <span class="error-text" role="alert">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="email" class="form-label">
                Email Address
            </label>

            <input
                type="email"
                id="email"
                name="email"
                class="form-control @error('email') is-invalid @enderror"
                value="{{ old('email') }}"
                placeholder="name@example.com"
                autocomplete="email"
                required
            >

            @error('email')
                <span class="error-text" role="alert">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="password" class="form-label">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                class="form-control @error('password') is-invalid @enderror"
                placeholder="Minimum 8 characters"
                autocomplete="new-password"
                minlength="8"
                required
            >

            @error('password')
                <span class="error-text" role="alert">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
            <label for="password_confirmation" class="form-label">
                Confirm Password
            </label>

            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                class="form-control"
                placeholder="Confirm your password"
                autocomplete="new-password"
                minlength="8"
                required
            >
        </div>

        <button type="submit" class="btn-primary">
            Create Account
        </button>

    </form>

    <div class="form-bottom">
        Already have an account?

        <a href="{{ route('login') }}">
            Sign in
        </a>
    </div>

@endsection