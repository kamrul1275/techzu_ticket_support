<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Throwable;

class TicketController extends Controller
{
    /**
     * Display tickets based on user role.
     */
    public function index(Request $request): View|RedirectResponse
    {
        Gate::authorize('viewAny', Ticket::class);

        try {
            $user = $request->user();

            $query = Ticket::with([
                'customer:id,name',
                'assignedAgent:id,name',
                'category:id,name',
            ]);

            // Customer: only own tickets
            if ($user->isCustomer()) {
                $query->where('customer_id', $user->id);
            }

            // Agent: only assigned tickets
            if ($user->isAgent()) {
                $query->where('assigned_agent_id', $user->id);
            }

            // Admin: all tickets
            $tickets = $query
                ->latest()
                ->paginate(10);

            return view('tickets.index', compact('tickets'));

        } catch (Throwable $e) {

            Log::error('Failed to load tickets', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return redirect()
                ->route('dashboard')
                ->with('error', 'Unable to load tickets.');
        }
    }

    /**
     * Show the create ticket form.
     */
    public function create(): View
    {
        Gate::authorize('create', Ticket::class);

        // Get only active categories
        $categories = TicketCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('tickets.create', compact('categories'));
    }

    /**
     * Store a new ticket.
     */
    public function store(
        StoreTicketRequest $request,
        TicketService $ticketService
    ): RedirectResponse {

        try {
            // Validated data is sent to the service
            $ticket = $ticketService->createTicket(
                $request->user(),
                $request->validated()
            );

            return redirect()
                ->route('tickets.index')
                ->with(
                    'success',
                    "Ticket {$ticket->ticket_number} created successfully."
                );

        } catch (Throwable $e) {

            Log::error('Ticket creation failed', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->only([
                    'category_id',
                    'subject',
                    'description',
                    'priority',
                ]))
                ->with('error', 'Unable to create ticket. Please try again.');
        }
    }

    /**
     * Display a single ticket.
     *
     * JSON response for now.
     * Blade details page will be added next.
     */

/**
 * Show ticket details page.
 */
public function show(Ticket $ticket): View|RedirectResponse
{
    // Check whether the user can view this ticket
    Gate::authorize('view', $ticket);

    try {
        // Load ticket-related information
        $ticket->load([
            'customer:id,name',
            'assignedAgent:id,name',
            'category:id,name',
        ]);

// Load activity and assignment history for staff
$user = auth()->user();

if ($user->isAdmin() || $user->isAgent()) {
    $ticket->load([
        'activities.user:id,name',
        'assignments.agent:id,name',
        'assignments.assignedBy:id,name',
    ]);
}
// Load support agents for admin
$agents = collect();

if ($user->isAdmin()) {
    $agents = User::where('role', 'agent')
        ->orderBy('name')
        ->get(['id', 'name']);
}

// Load ticket conversation with pagination
$messages = $ticket->messages()
    ->with([
        'user:id,name',
        'attachments:id,ticket_message_id,original_name,size_bytes',
    ])
    // Never load internal notes for customers
    ->when(
        $user->isCustomer(),
        fn ($query) => $query->where('is_internal', false)
    )
    ->orderBy('created_at')
    ->orderBy('id')
    ->paginate(15, ['*'], 'messages_page');

// Calculate current SLA status
$slaStatus = app(\App\Services\TicketSlaService::class)
    ->status($ticket);

return view(
    'tickets.show',
    compact('ticket', 'agents', 'messages', 'slaStatus')
);

    } catch (Throwable $e) {

        Log::error('Failed to load ticket details', [
            'ticket_id' => $ticket->id,
            'user_id' => auth()->id(),
            'error' => $e->getMessage(),
        ]);

        return redirect()
            ->route('tickets.index')
            ->with('error', 'Unable to load ticket details.');
    }
}







    public function edit(Ticket $ticket): View
    {
        // Check user permission
        Gate::authorize('update', $ticket);

        // Load active categories and current category
        $categories = TicketCategory::where(function ($query) use ($ticket) {
            $query->where('is_active', true)
                  ->orWhere('id', $ticket->category_id);
        })
        ->orderBy('name')
        ->get();

        return view('tickets.edit', compact('ticket', 'categories'));
    }


    /**
     * Update ticket information.
     */
/**
 * Update ticket information.
 */
public function update(
    UpdateTicketRequest $request,
    Ticket $ticket,
    TicketService $ticketService
): RedirectResponse {

    try {
        $ticket = $ticketService->updateTicket(
            $ticket,
            $request->user(),
            $request->validated()
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket updated successfully.');

    } catch (Throwable $e) {

        Log::error('Ticket update failed', [
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'error' => $e->getMessage(),
        ]);

        return back()
            ->withInput()
            ->with('error', 'Unable to update ticket.');
    }
}

    /**
     * Resolve an active ticket.
     */
/**
 * Resolve a ticket.
 */
public function resolve(
    Request $request,
    Ticket $ticket,
    TicketService $ticketService
): RedirectResponse {

    Gate::authorize('resolve', $ticket);

    try {
        $ticketService->resolveTicket(
            $ticket,
            $request->user()
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket resolved successfully.');

    } catch (Throwable $e) {

        Log::error('Ticket resolve failed', [
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'error' => $e->getMessage(),
        ]);

        return back()
            ->with('error', 'Unable to resolve ticket.');
    }
}
    /**
     * Close a resolved ticket.
     */
/**
 * Close a resolved ticket.
 */
public function close(
    Request $request,
    Ticket $ticket,
    TicketService $ticketService
): RedirectResponse {

    Gate::authorize('close', $ticket);

    try {
        $ticketService->closeTicket(
            $ticket,
            $request->user()
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket closed successfully.');

    } catch (Throwable $e) {

        Log::error('Ticket close failed', [
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'error' => $e->getMessage(),
        ]);

        return back()
            ->with('error', 'Unable to close ticket.');
    }
}

    /**
     * Handle and log unexpected errors.
     */
    private function handleError(
        Throwable $e,
        string $action,
        ?int $ticketId = null
    ): JsonResponse {

        Log::error("Ticket {$action} failed", [
            'user_id' => auth()->id(),
            'ticket_id' => $ticketId,
            'exception' => get_class($e),
            'error' => $e->getMessage(),
        ]);

        return response()->json([
            'message' => 'Something went wrong. Please try again.',
        ], 500);
    }


    /**
 * Assign a ticket manually.
 */
public function assign(
    Request $request,
    Ticket $ticket,
    TicketService $ticketService
): RedirectResponse {

    Gate::authorize('assign', $ticket);

    // Validate selected agent
    $data = $request->validate([
        'agent_id' => [
            'required',
            'integer',
            Rule::exists('users', 'id')->where('role', 'agent'),
        ],
    ]);

    try {
        $ticketService->assignTicket(
            $ticket,
            $request->user(),
            (int) $data['agent_id']
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket assigned successfully.');

    } catch (ValidationException $e) {
        throw $e;

    } catch (Throwable $e) {

        Log::error('Ticket assignment failed', [
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'error' => $e->getMessage(),
        ]);

        return back()
            ->with('error', 'Unable to assign ticket.');
    }
}

/**
 * Show available unassigned tickets to agents.
 */
public function available(Request $request): View|RedirectResponse
{
    // Only agents can access this page
    abort_unless($request->user()->isAgent(), 403);

    try {
        $tickets = Ticket::with([
                'customer:id,name',
                'category:id,name',
            ])
            ->where('status', 'open')
            ->whereNull('assigned_agent_id')
            ->latest()
            ->paginate(10);

        return view('tickets.available', compact('tickets'));

    } catch (Throwable $e) {

        Log::error('Failed to load available tickets', [
            'user_id' => $request->user()->id,
            'error' => $e->getMessage(),
        ]);

        return redirect()
            ->route('tickets.index')
            ->with('error', 'Unable to load available tickets.');
    }
}

/**
 * Accept an available ticket.
 */
public function accept(
    Request $request,
    Ticket $ticket,
    TicketService $ticketService
): RedirectResponse {

    // Agent permission and ticket availability
    Gate::authorize('accept', $ticket);

    try {
        $ticketService->acceptTicket(
            $ticket,
            $request->user()
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with('success', 'Ticket accepted successfully.');

    } catch (ValidationException $e) {

        // Show validation error instead of server error
        throw $e;

    } catch (Throwable $e) {

        Log::error('Ticket acceptance failed', [
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'error' => $e->getMessage(),
        ]);

        return back()
            ->with('error', 'Unable to accept ticket.');
    }
}





/**
 * Automatically assign a ticket to the least busy agent.
 */
public function autoAssign(
    Request $request,
    Ticket $ticket,
    TicketService $ticketService
): RedirectResponse {

    // Check admin assignment permission
    Gate::authorize('assign', $ticket);

    try {

        $ticket = $ticketService->autoAssignTicket(
            $ticket,
            $request->user()
        );

        return redirect()
            ->route('tickets.show', $ticket)
            ->with(
                'success',
                'Ticket automatically assigned to '
                . $ticket->assignedAgent->name . '.'
            );

    } catch (ValidationException $e) {

        throw $e;

    } catch (Throwable $e) {

        Log::error('Automatic ticket assignment failed', [
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'error' => $e->getMessage(),
        ]);

        return back()
            ->with('error', 'Unable to automatically assign ticket.');
    }
}
}