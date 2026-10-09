
@extends('layouts.app')

@section('title', 'Ticket Details')

@section('content')

    {{-- ======================================
        PAGE HEADER
    ====================================== --}}
    <div class="page-header">

        <div>
            <span class="detail-ticket-number">
                {{ $ticket->ticket_number }}
            </span>

            <h1>{{ $ticket->subject }}</h1>

            <p>View ticket information and current progress.</p>
        </div>

        <div class="ticket-detail-actions">

            {{-- Edit Ticket --}}
            @can('update', $ticket)
                <a href="{{ route('tickets.edit', $ticket) }}"
                   class="ticket-edit-btn">
                    Edit
                </a>
            @endcan

            {{-- Resolve Ticket --}}
            @can('resolve', $ticket)
                <form method="POST"
                      action="{{ route('tickets.resolve', $ticket) }}"
                      class="js-confirm-ticket"
                      data-title="Resolve Ticket?"
                      data-message="Are you sure this issue has been resolved?">

                    @csrf
                    @method('PATCH')

                    <button type="submit" class="btn-resolve">
                        Resolve Ticket
                    </button>
                </form>
            @endcan

            {{-- Close Ticket --}}
            @can('close', $ticket)
                <form method="POST"
                      action="{{ route('tickets.close', $ticket) }}"
                      class="js-confirm-ticket"
                      data-title="Close Ticket?"
                      data-message="Are you sure you want to close this ticket?">

                    @csrf
                    @method('PATCH')

                    <button type="submit" class="btn-close-ticket">
                        Close Ticket
                    </button>
                </form>
            @endcan

            {{-- Back to Tickets --}}
            <a href="{{ route('tickets.index') }}"
               class="btn-secondary">
                Back to Tickets
            </a>

        </div>

    </div>


    {{-- ======================================
        TICKET SUMMARY CARDS
    ====================================== --}}
    <div class="detail-summary">

        {{-- Status --}}
        <div class="detail-summary-item">
            <span>Current Status</span>

            <div>
                <span class="status-badge status-{{ $ticket->status }}">
                    {{ ucwords(str_replace('_', ' ', $ticket->status)) }}
                </span>
            </div>
        </div>

        {{-- Priority --}}
        <div class="detail-summary-item">
            <span>Priority</span>

            <div>
                <span class="priority-badge priority-{{ $ticket->priority }}">
                    {{ ucfirst($ticket->priority) }}
                </span>
            </div>
        </div>

        {{-- Category --}}
        <div class="detail-summary-item">
            <span>Category</span>

            <strong>
                {{ $ticket->category?->name ?? 'N/A' }}
            </strong>
        </div>

        {{-- Assigned Agent --}}
        <div class="detail-summary-item">
            <span>Assigned Agent</span>

            <strong>
                {{ $ticket->assignedAgent?->name ?? 'Unassigned' }}
            </strong>
        </div>

    </div>


    {{-- ======================================
        MANUAL AGENT ASSIGNMENT (ADMIN ONLY)
    ====================================== --}}
    @can('assign', $ticket)

        <div class="content-card mb-4">

            <div class="card-heading">
                <div>
                    <h2>Assign Support Agent</h2>

                    <p class="ticket-subtitle">
                        Select an agent to handle this ticket.
                    </p>
                </div>

                @if($ticket->assigned_agent_id)
                    <span class="badge bg-success">
                        Assigned
                    </span>
                @else
                    <span class="badge bg-secondary">
                        Unassigned
                    </span>
                @endif
            </div>

            <form method="POST"
                  action="{{ route('tickets.assign', $ticket) }}"
                  class="row g-3 align-items-end">

                @csrf
                @method('PATCH')

                {{-- Agent Dropdown --}}
                <div class="col-md-8">

                    <label for="agent_id" class="form-label">
                        Select Support Agent
                        <span class="text-danger">*</span>
                    </label>

                    <select
                        name="agent_id"
                        id="agent_id"
                        class="form-select form-select-sm @error('agent_id') is-invalid @enderror"
                        required
                    >

                        <option value="">Select Agent</option>

                        @foreach($agents as $agent)
                            <option
                                value="{{ $agent->id }}"
                                @selected(
                                    old('agent_id', $ticket->assigned_agent_id)
                                    == $agent->id
                                )
                            >
                                {{ $agent->name }}
                            </option>
                        @endforeach

                    </select>

                    @error('agent_id')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                    @if($agents->isEmpty())
                        <small class="text-danger">
                            No support agents available.
                        </small>
                    @endif

                </div>

                {{-- Submit Button --}}
                <div class="col-md-4">

                    <button type="submit"
                            class="btn btn-success btn-sm w-100"
                            @disabled($agents->isEmpty())>

                        @if($ticket->assigned_agent_id)
                            Reassign Agent
                        @else
                            Assign Agent
                        @endif

                    </button>

                </div>

            </form>

            {{-- Current Assignment --}}
            @if($ticket->assignedAgent)

                <div class="mt-3 pt-3 border-top">
                    <small class="text-muted">
                        Currently assigned to:
                        <strong>
                            {{ $ticket->assignedAgent->name }}
                        </strong>
                    </small>
                </div>

            @endif

        </div>

    @endcan


    {{-- ======================================
        TICKET INFORMATION CARD
    ====================================== --}}
    <div class="content-card ticket-detail-card">

        <div class="card-heading">
            <h2>Ticket Information</h2>
        </div>

        <div class="detail-info-grid">

            {{-- Ticket Number --}}
            <div class="detail-info">
                <span>Ticket Number</span>
                <strong>{{ $ticket->ticket_number }}</strong>
            </div>

            {{-- Created By --}}
            <div class="detail-info">
                <span>Created By</span>

                <strong>
                    {{ $ticket->customer?->name ?? 'Unknown' }}
                </strong>
            </div>

            {{-- Created Date --}}
            <div class="detail-info">
                <span>Created Date</span>

                <strong>
                    {{ $ticket->created_at->format('d M Y, h:i A') }}
                </strong>
            </div>

            {{-- Last Updated --}}
            <div class="detail-info">
                <span>Last Updated</span>

                <strong>
                    {{ $ticket->updated_at->format('d M Y, h:i A') }}
                </strong>
            </div>

            {{-- SLA Deadline --}}
            <div class="detail-info">
                <span>SLA Deadline</span>

                <strong>
                    @if($ticket->sla_due_at)
                        {{ $ticket->sla_due_at->format('d M Y, h:i A') }}
                    @else
                        Not calculated yet
                    @endif
                </strong>
            </div>

            {{-- Resolved Date --}}
            <div class="detail-info">
                <span>Resolved Date</span>

                <strong>
                    {{ $ticket->resolved_at?->format('d M Y, h:i A') ?? 'Not resolved' }}
                </strong>
            </div>

        </div>

        {{-- Description --}}
        <div class="detail-description">

            <h3>Description</h3>

            <div class="detail-description-text">{{ $ticket->description }}</div>

        </div>

    </div>


    {{-- ======================================
        ACTIVITY HISTORY (ADMIN / AGENT)
    ====================================== --}}
    @if(auth()->user()->isAdmin() || auth()->user()->isAgent())

        <div class="content-card ticket-history-card">

            <div class="card-heading">
                <h2>Activity History</h2>
            </div>

            @forelse($ticket->activities->sortByDesc('created_at') as $activity)

                <div class="detail-activity">

                    <span class="detail-activity-dot"></span>

                    <div>

                        <strong>
                            {{ ucwords(str_replace('_', ' ', $activity->action)) }}
                        </strong>

                        <p>
                            By {{ $activity->user?->name ?? 'System' }}
                        </p>

                        <small>
                            {{ $activity->created_at->format('d M Y, h:i A') }}
                        </small>

                    </div>

                </div>

            @empty

                <p class="detail-empty-history">
                    No activity recorded yet.
                </p>

            @endforelse

        </div>

    @endif

@endsection


{{-- ======================================
    SWEETALERT CONFIRMATION
====================================== --}}
@push('scripts')
<script>
    document.querySelectorAll('.js-confirm-ticket').forEach(function(form) {

        form.addEventListener('submit', function(event) {
            event.preventDefault();

            const title = form.dataset.title;
            const message = form.dataset.message;

            // Fallback if SweetAlert2 is unavailable
            if (typeof Swal === 'undefined') {
                if (confirm(message)) {
                    form.submit();
                }
                return;
            }

            Swal.fire({
                title: title,
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, Continue',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#059669',
                cancelButtonColor: '#64748b'
            }).then(function(result) {

                if (result.isConfirmed) {
                    form.submit();
                }

            });
        });

    });
</script>
@endpush
