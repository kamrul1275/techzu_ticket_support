
@extends('layouts.app')

@section('title', 'Edit Ticket')

@section('content')

    {{-- Page Header --}}
    <div class="page-header">

        <div>
            <h1>Edit Ticket</h1>
            <p>Update your support ticket information.</p>
        </div>

        <a href="{{ route('tickets.show', $ticket) }}"
           class="btn-secondary">
            ← Back to Details
        </a>

    </div>

    {{-- Edit Form Card --}}
    <div class="content-card form-card ticket-edit-card">

        {{-- Card Header --}}
        <div class="card-heading">

            <div>
                <h2>Ticket Information</h2>

                <p class="ticket-subtitle">
                    {{ $ticket->ticket_number }}
                </p>
            </div>

            <span class="status-badge status-{{ $ticket->status }}">
                {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
            </span>

        </div>

        {{-- Form --}}
        <form method="POST"
              action="{{ route('tickets.update', $ticket) }}">

            @csrf
            @method('PATCH')

            {{-- Subject --}}
            <div class="form-group">

                <label for="subject" class="form-label">
                    Subject <span class="required">*</span>
                </label>

                <input
                    type="text"
                    id="subject"
                    name="subject"
                    class="form-control @error('subject') is-invalid @enderror"
                    value="{{ old('subject', $ticket->subject) }}"
                    placeholder="Enter ticket subject"
                    maxlength="200"
                    required
                >

                @error('subject')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>

            {{-- Category --}}
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

                    @foreach($categories as $category)

                        <option
                            value="{{ $category->id }}"
                            @selected(
                                old('category_id', $ticket->category_id)
                                == $category->id
                            )
                        >
                            {{ $category->name }}
                        </option>

                    @endforeach

                </select>

                @error('category_id')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>

            {{-- Priority --}}
            <div class="form-group">

                <label for="priority" class="form-label">
                    Priority
                </label>

                @if(auth()->user()->isCustomer())

                    {{-- Customer cannot change priority --}}
                    <input
                        type="text"
                        id="priority"
                        class="form-control readonly-input"
                        value="{{ ucfirst($ticket->priority) }}"
                        disabled
                    >

                @else

                    {{-- Admin and Agent can update priority --}}
                    <select
                        id="priority"
                        name="priority"
                        class="form-control @error('priority') is-invalid @enderror"
                    >

                        @foreach(['low', 'medium', 'high', 'critical'] as $priority)

                            <option
                                value="{{ $priority }}"
                                @selected(
                                    old('priority', $ticket->priority)
                                    == $priority
                                )
                            >
                                {{ ucfirst($priority) }}
                            </option>

                        @endforeach

                    </select>

                    @error('priority')
                        <span class="error-text">
                            {{ $message }}
                        </span>
                    @enderror

                @endif

            </div>

            {{-- Description --}}
            <div class="form-group">

                <label for="description" class="form-label">
                    Description <span class="required">*</span>
                </label>

                <textarea
                    id="description"
                    name="description"
                    class="form-control ticket-textarea @error('description') is-invalid @enderror"
                    placeholder="Describe your issue..."
                    rows="4"
                    required
                >{{ old('description', $ticket->description) }}</textarea>

                @error('description')
                    <span class="error-text">
                        {{ $message }}
                    </span>
                @enderror

            </div>

            {{-- Footer Buttons --}}
            <div class="form-actions">

                <a href="{{ route('tickets.show', $ticket) }}"
                   class="btn-secondary">
                    Cancel
                </a>

                <button type="submit" class="btn-create">
                    Save Changes
                </button>

            </div>

        </form>

    </div>

@endsection
