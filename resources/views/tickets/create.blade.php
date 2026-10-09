@extends('layouts.dashboard')

@section('title', 'Create Ticket')

@section('content')

    <!-- Page Header -->
    <div class="page-header">

        <div>
            <h1>Create Support Ticket</h1>

            <p>
                Tell us about your issue.
                Our support team will help you.
            </p>
        </div>

        <a href="{{ route('tickets.index') }}"
           class="btn-secondary">
            Back to Tickets
        </a>

    </div>

    <div class="content-card form-card">

        <div class="card-heading">
            <h2>Ticket Information</h2>
        </div>

        <form method="POST" action="{{ route('tickets.store') }}">
            @csrf

            <!-- Subject -->
            <div class="form-group">

                <label for="subject" class="form-label">
                    Subject <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="subject"
                    name="subject"
                    class="form-control @error('subject') is-invalid @enderror"
                    value="{{ old('subject') }}"
                    placeholder="Enter ticket subject"
                    maxlength="200"
                    required
                >

                @error('subject')
                    <span class="error-text">{{ $message }}</span>
                @enderror

            </div>

            <!-- Category -->
            <div class="form-group">

                <label for="category_id" class="form-label">
                    Category <span class="required">*</span>
                </label>

                <select
                    id="category_id"
                    name="category_id"
                    class="form-control @error('category_id') is-invalid @enderror"
                    required
                >

                    <option value="">Select Category</option>

                    @foreach($categories as $category)
                        <option
                            value="{{ $category->id }}"
                            @selected(old('category_id') == $category->id)
                        >
                            {{ $category->name }}
                        </option>
                    @endforeach

                </select>

                @error('category_id')
                    <span class="error-text">{{ $message }}</span>
                @enderror

            </div>

            <!-- Priority -->
            <div class="form-group">

                <label for="priority" class="form-label">
                    Priority <span class="required">*</span>
                </label>

                <select
                    id="priority"
                    name="priority"
                    class="form-control @error('priority') is-invalid @enderror"
                    required
                >

                    <option value="">Select Priority</option>

                    <option value="low" @selected(old('priority') == 'low')>
                        Low
                    </option>

                    <option value="medium" @selected(old('priority') == 'medium')>
                        Medium
                    </option>

                    <option value="high" @selected(old('priority') == 'high')>
                        High
                    </option>

                    <option value="critical" @selected(old('priority') == 'critical')>
                        Critical
                    </option>

                </select>

                @error('priority')
                    <span class="error-text">{{ $message }}</span>
                @enderror

            </div>

            <!-- Description -->
            <div class="form-group">

                <label for="description" class="form-label">
                    Description <span class="required">*</span>
                </label>

                <textarea
                    id="description"
                    name="description"
                    class="form-control ticket-textarea @error('description') is-invalid @enderror"
                    placeholder="Describe your issue in detail..."
                    rows="6"
                    required
                >{{ old('description') }}</textarea>

                @error('description')
                    <span class="error-text">{{ $message }}</span>
                @enderror

            </div>

            <!-- Submit Buttons -->
            <div class="form-actions">

                <a href="{{ route('tickets.index') }}"
                   class="btn-secondary">
                    Cancel
                </a>

                <button type="submit" class="btn-create">
                    Submit Ticket
                </button>

            </div>

        </form>

    </div>

@endsection