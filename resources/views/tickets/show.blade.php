
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


            {{-- Automatic Assignment --}}
            @if(is_null($ticket->assigned_agent_id))

                <div class="mt-3 pt-3 border-top">

                    <div class="d-flex flex-wrap
                                align-items-center
                                justify-content-between
                                gap-3">

                        <div>
                            <strong class="d-block small">
                                Automatic Assignment
                            </strong>

                            <small class="text-muted">
                                Assign this ticket to the agent
                                with the fewest active tickets.
                            </small>
                        </div>

                        <form
                            method="POST"
                            action="{{ route('tickets.auto-assign', $ticket) }}"
                            class="js-confirm-ticket"
                            data-title="Auto Assign Ticket?"
                            data-message="The system will select the agent with the lowest workload."
                        >
                            @csrf
                            @method('PATCH')

                            <button
                                type="submit"
                                class="btn btn-outline-success btn-sm"
                                @disabled($agents->isEmpty())
                            >
                                Auto Assign
                            </button>

                        </form>

                    </div>

                    @error('auto_assign')
                        <div class="alert alert-warning mt-3 mb-0">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

            @endif

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

            {{ $ticket->sla_due_at
                ->copy()
                ->timezone(config('supportdesk.timezone'))
                ->format('d M Y, h:i A') }}

        @else
            Not calculated yet
        @endif
    </strong>

</div>

{{-- SLA Status --}}
<div class="detail-info">

    <span>SLA Status</span>

    @php
        $slaLabels = [
            'not_set' => 'Not Calculated',
            'within' => 'Within SLA',
            'approaching' => 'Approaching Breach',
            'breached' => 'SLA Breached',
            'met' => 'SLA Met',
        ];
    @endphp

    <div>
        <span class="sla-status sla-status-{{ $slaStatus }}">
            {{ $slaLabels[$slaStatus] ?? 'Unknown' }}
        </span>
    </div>

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
    TICKET CONVERSATION
====================================== --}}
<div class="content-card ticket-conversation-card">

    <div class="card-heading">
        <div>
            <h2>Ticket Conversation</h2>
            <p>Replies and conversation history.</p>
        </div>

        <span class="conversation-count">
            {{ $messages->total() }} Messages
        </span>
    </div>

    {{-- Message List --}}
    <div class="conversation-list">

        @forelse($messages as $message)

            <div class="conversation-message
                {{ $message->is_internal ? 'conversation-internal' : '' }}
                {{ $message->user_id === auth()->id() ? 'conversation-own' : '' }}">

                <div class="conversation-message-header">

                    <div class="conversation-author">
                        <strong>
                            {{ $message->user?->name ?? 'Unknown User' }}
                        </strong>

                        @if($message->is_internal)
                            <span class="conversation-internal-badge">
                                Internal Note
                            </span>
                        @elseif($message->user?->isAgent())
                            <span class="conversation-agent-badge">
                                Support Agent
                            </span>
                        @elseif($message->user?->isAdmin())
                            <span class="conversation-agent-badge">
                                Admin
                            </span>
                        @else
                            <span class="conversation-customer-badge">
                                Customer
                            </span>
                        @endif
                    </div>

                    <small>
                        {{ $message->created_at->format('d M Y, h:i A') }}
                    </small>

                </div>

                {{-- Message Body --}}
                <div class="conversation-message-body">{{ $message->body }}</div>

                {{-- Attachments --}}
                @if($message->attachments->isNotEmpty())

                    <div class="conversation-attachments">

                        @foreach($message->attachments as $attachment)

                            <a href="{{ route('tickets.attachments.download', [$ticket, $attachment]) }}"
                               class="conversation-file">

                                <span>📎</span>

                                <span>
                                    {{ $attachment->original_name }}
                                </span>

                                <small>
                                    {{ number_format($attachment->size_bytes / 1024, 1) }} KB
                                </small>

                            </a>

                        @endforeach

                    </div>

                @endif

            </div>

        @empty

            <div class="conversation-empty">
                <h3>No Messages Yet</h3>
                <p>Start the conversation by sending a reply.</p>
            </div>

        @endforelse

    </div>

    {{-- Pagination --}}
    @if($messages->hasPages())
        <div class="mt-3">
            {{ $messages->links('pagination::bootstrap-5') }}
        </div>
    @endif

    {{-- Reply / Internal Note Form --}}
    @canany(['reply', 'addInternalNote'], $ticket)

        <div class="conversation-compose">

            <h3>Write a Message</h3>

            <form method="POST"
                  action="{{ route('tickets.messages.store', $ticket) }}"
                  enctype="multipart/form-data">

                @csrf

                {{-- Message Type --}}
                <div class="mb-3">

                    <label for="message_type" class="form-label">
                        Message Type
                    </label>

                    <select name="type"
                            id="message_type"
                            class="form-select form-select-sm @error('type') is-invalid @enderror"
                            required>

                        @can('reply', $ticket)
                            <option value="reply"
                                @selected(old('type', 'reply') === 'reply')>
                                Public Reply
                            </option>
                        @endcan

                        @can('addInternalNote', $ticket)
                            <option value="note"
                                @selected(old('type') === 'note')>
                                Internal Note (Staff Only)
                            </option>
                        @endcan

                    </select>

                    @error('type')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                {{-- Message Body --}}
                <div class="mb-3">

                    <label for="message_body" class="form-label">
                        Your Message <span class="text-danger">*</span>
                    </label>

                    <textarea
                        name="body"
                        id="message_body"
                        rows="4"
                        maxlength="5000"
                        required
                        placeholder="Write your reply..."
                        class="form-control @error('body') is-invalid @enderror"
                    >{{ old('body') }}</textarea>

                    @error('body')
                        <div class="invalid-feedback">
                            {{ $message }}
                        </div>
                    @enderror

                </div>

                {{-- Attachment Input --}}
                <div class="mb-3">

                    <label for="message_files" class="form-label">
                        Attachments (Optional)
                    </label>

                    <input
                        type="file"
                        id="message_files"
                        name="attachments[]"
                        class="form-control form-control-sm"
                        accept=".jpg,.jpeg,.png,.pdf,.txt"
                        multiple
                    >

                    <small class="text-muted">
                        Maximum 3 files, 5 MB each.
                        Allowed: JPG, PNG, PDF, TXT.
                    </small>

                    @if($errors->has('attachments') || $errors->has('attachments.*'))
                        <div class="text-danger small mt-2">
                            {{ $errors->first('attachments')
                               ?: $errors->first('attachments.*') }}
                        </div>
                    @endif

                </div>

                {{-- Actions --}}
                <div class="conversation-form-actions">

                    <span class="conversation-security-note">
                        Internal notes are visible to support staff only.
                    </span>

                    <button type="submit" class="conversation-send-btn">
                        Send Message
                    </button>

                </div>

            </form>

        </div>

    @else

        <div class="conversation-readonly">
            This ticket is completed. New replies are disabled.
        </div>

    @endcanany

</div>




{{-- ======================================
    ASSIGNMENT HISTORY (ADMIN / AGENT)
====================================== --}}
@if(auth()->user()->isAdmin() || auth()->user()->isAgent())

    <div class="content-card ticket-history-card">

        <div class="card-heading">
            <h2>Assignment History</h2>
        </div>

        @forelse($ticket->assignments->sortByDesc('id') as $assignment)

            @php
                if ($assignment->method === 'auto') {
                    $assignmentType = 'Automatic Assignment';
                    $assignmentBadge = 'bg-success';
                } elseif (
                    (int) $assignment->assigned_by ===
                    (int) $assignment->agent_id
                ) {
                    $assignmentType = 'Agent Accepted';
                    $assignmentBadge = 'bg-info text-dark';
                } else {
                    $assignmentType = 'Manual Assignment';
                    $assignmentBadge = 'bg-secondary';
                }
            @endphp

            <div class="detail-activity">

                <span class="detail-activity-dot"></span>

                <div class="w-100">

                    <div class="d-flex flex-wrap
                                align-items-center gap-2 mb-2">

                        <strong>{{ $assignmentType }}</strong>

                        <span class="badge {{ $assignmentBadge }}">
                            {{ ucfirst($assignment->method) }}
                        </span>

                    </div>

                    <p>
                        Assigned to:
                        <strong>
                            {{ $assignment->agent?->name ?? 'Unknown Agent' }}
                        </strong>
                    </p>

                    <p>
                        Assigned by:
                        <strong>
                            {{ $assignment->assignedBy?->name ?? 'System' }}
                        </strong>
                    </p>

                    <small>
                        {{ $assignment->created_at->format('d M Y, h:i A') }}
                    </small>

                </div>

            </div>

        @empty

            <p class="detail-empty-history">
                No assignment history recorded yet.
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
