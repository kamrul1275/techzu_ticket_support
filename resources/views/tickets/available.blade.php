
@extends('layouts.app')

@section('title', 'Available Tickets')

@section('content')

    {{-- Page Header --}}
    <div class="page-header">

        <div>
            <h1>Available Tickets</h1>
            <p>Browse and accept unassigned support requests.</p>
        </div>

        <a href="{{ route('tickets.index') }}"
           class="btn-secondary">
            My Tickets
        </a>

    </div>

    {{-- Validation Error --}}
    @if($errors->has('ticket'))
        <div class="alert alert-warning">
            {{ $errors->first('ticket') }}
        </div>
    @endif

    {{-- Available Tickets Card --}}
    <div class="content-card available-ticket-card">

        <div class="card-heading">

            <div>
                <h2>Unassigned Tickets</h2>
                <p>Tickets waiting for a support agent.</p>
            </div>

            <span class="available-count">
                {{ $tickets->total() }} Available
            </span>

        </div>

        <div class="table-responsive">

            <table class="table align-middle available-ticket-table">

                <thead>
                    <tr>
                        <th>Ticket ID</th>
                        <th>Subject</th>
                        <th>Category</th>
                        <th>Priority</th>
                        <th>Created</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($tickets as $ticket)

                        <tr>

                            {{-- Ticket Number --}}
                            <td>
                                <span class="available-ticket-number">
                                    {{ $ticket->ticket_number }}
                                </span>
                            </td>

                            {{-- Subject --}}
                            <td>
                                <div class="available-subject">
                                    {{ $ticket->subject }}
                                </div>

                                <small class="available-customer">
                                    By {{ $ticket->customer?->name ?? 'Unknown' }}
                                </small>
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

                            {{-- Created Date --}}
                            <td>
                                {{ $ticket->created_at->format('d M Y') }}
                            </td>

                            {{-- Accept Ticket --}}
                            <td>

                                <form
                                    method="POST"
                                    action="{{ route('tickets.accept', $ticket) }}"
                                    class="js-accept-ticket"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button type="submit"
                                            class="available-accept-btn">
                                        Accept Ticket
                                    </button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="6"
                                class="text-center py-5">

                                <div class="available-empty">

                                    <h3>No Available Tickets</h3>

                                    <p>
                                        All open tickets are currently assigned.
                                    </p>

                                </div>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

        {{-- Pagination --}}
        @if($tickets->hasPages())

            <div class="available-pagination">
                {{ $tickets->links('pagination::bootstrap-5') }}
            </div>

        @endif

    </div>

@endsection


@push('scripts')

<script>

    document.querySelectorAll('.js-accept-ticket').forEach(function(form) {

        form.addEventListener('submit', function(event) {

            event.preventDefault();

            // Fallback when SweetAlert2 is unavailable
            if (typeof Swal === 'undefined') {

                if (confirm('Do you want to accept this ticket?')) {
                    form.submit();
                }

                return;
            }

            Swal.fire({
                title: 'Accept Ticket?',
                text: 'This ticket will be assigned to you.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, Accept',
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
