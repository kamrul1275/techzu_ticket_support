
@extends('layouts.app')

@section('title', 'Support Tickets')

@section('content')

    {{-- Available Tickets Shortcut for Agents --}}
    @if(auth()->user()->isAgent())

        <div class="d-flex justify-content-end mb-3">

            <a href="{{ route('tickets.available') }}"
               class="btn btn-success btn-sm">
                Available Tickets
            </a>

        </div>

    @endif

    {{-- Page Header --}}
    <div class="page-header">

        <div>
            <h1>Support Tickets</h1>
            <p>Manage, monitor and track your support requests.</p>
        </div>

        @can('create', App\Models\Ticket::class)
            <a href="{{ route('tickets.create') }}" class="btn-create">
                + Create Ticket
            </a>
        @endcan

    </div>

    {{-- Ticket List Card --}}
    <div class="content-card">

        <div class="card-heading">
            <div>
                <h2>All Tickets</h2>
                <p class="ticket-subtitle">
                    View your support ticket history and current progress.
                </p>
            </div>

            <span class="ticket-count">
                {{ $tickets->total() }} Tickets
            </span>
        </div>

        {{-- Table --}}
        <div class="table-responsive">

            <table class="ticket-table">

                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created Date</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($tickets as $ticket)

                        <tr>
                            {{-- Ticket Number --}}
                            <td>
                                <span class="ticket-number">
                                    {{ $ticket->ticket_number }}
                                </span>
                            </td>

                            {{-- Subject --}}
                            <td>
                                <div class="ticket-subject">
                                    <strong>{{ $ticket->subject }}</strong>

                                    <small>
                                        {{ \Illuminate\Support\Str::limit($ticket->description, 55) }}
                                    </small>
                                </div>
                            </td>

                            {{-- Category --}}
                            <td>
                                {{ $ticket->category?->name ?? 'N/A' }}
                            </td>

                            {{-- Priority --}}
                            <td>
                                <span class="priority-badge priority-{{ $ticket->priority }}">
                                    {{ ucfirst($ticket->priority) }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td>
                                <span class="status-badge status-{{ $ticket->status }}">
                                    {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
                                </span>
                            </td>

                            {{-- Date --}}
                            <td>
                                {{ $ticket->created_at->format('d M Y') }}
                            </td>

                         
                {{-- Action --}}
                <td>
                    <div class="ticket-actions">

                        {{-- View Details --}}
                        <a href="{{ route('tickets.show', $ticket) }}"
                        class="ticket-view-btn">
                            View
                        </a>

                        {{-- Edit Ticket --}}
                        @can('update', $ticket)
                            <a href="{{ route('tickets.edit', $ticket) }}"
                            class="ticket-edit-btn">
                                Edit
                            </a>
                        @endcan

                    </div>
                </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="7">
                                <div class="ticket-empty">

                                    <div class="ticket-empty-icon">
                                        &#9776;
                                    </div>

                                    <h3>No tickets found</h3>

                                    <p>
                                        You don't have any support tickets yet.
                                        Create your first ticket to get started.
                                    </p>

                                    @can('create', App\Models\Ticket::class)
                                        <a href="{{ route('tickets.create') }}"
                                           class="btn-create">
                                            + Create First Ticket
                                        </a>
                                    @endcan

                                </div>
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- Pagination --}}
        @if($tickets->hasPages())

            <div class="pagination">

                <span>
                    Showing {{ $tickets->firstItem() }}
                    to {{ $tickets->lastItem() }}
                    of {{ $tickets->total() }} tickets
                </span>

                <div class="pagination-actions">

                    @if($tickets->onFirstPage())
                        <span class="pagination-disabled">
                            Previous
                        </span>
                    @else
                        <a href="{{ $tickets->previousPageUrl() }}">
                            Previous
                        </a>
                    @endif

                    <span>
                        Page {{ $tickets->currentPage() }}
                        of {{ $tickets->lastPage() }}
                    </span>

                    @if($tickets->hasMorePages())
                        <a href="{{ $tickets->nextPageUrl() }}">
                            Next
                        </a>
                    @else
                        <span class="pagination-disabled">
                            Next
                        </span>
                    @endif

                </div>

            </div>

        @endif

    </div>

@endsection
