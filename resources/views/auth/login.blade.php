@extends('layouts.app')

@section('title', 'Login | SupportDesk')

@section('content')

    <div class="form-heading">
        <h2>Welcome back</h2>

        <p>
            Enter your credentials to access your account.
        </p>
    </div>

    <form method="POST" action="{{ route('login') }}">
        @csrf

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
                autofocus
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
                placeholder="Enter your password"
                autocomplete="current-password"
            >

            @error('password')
                <span class="error-text" role="alert">
                    {{ $message }}
                </span>
            @enderror
        </div>

        <!-- Submit -->
        <button type="submit" class="btn-primary">
            Sign In
        </button>

    </form>

    <div class="form-bottom">
        Don't have an account?

        <a href="{{ route('register') }}">
            Create an account
        </a>
    </div>

@endsection