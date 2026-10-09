<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Services\TicketService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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
    public function show(Ticket $ticket): JsonResponse
    {
        Gate::authorize('view', $ticket);

        try {
            $ticket->load([
                'customer:id,name',
                'assignedAgent:id,name',
                'category:id,name',
            ]);

            return response()->json([
                'ticket' => $ticket,
            ]);

        } catch (Throwable $e) {

            return $this->handleError(
                $e,
                'view',
                $ticket->id
            );
        }
    }

    /**
     * Update ticket information.
     */
    public function update(
        UpdateTicketRequest $request,
        Ticket $ticket,
        TicketService $ticketService
    ): JsonResponse {

        try {
            $ticket = $ticketService->updateTicket(
                $ticket,
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'message' => 'Ticket updated successfully.',
                'ticket' => $ticket,
            ]);

        } catch (Throwable $e) {

            return $this->handleError(
                $e,
                'update',
                $ticket->id
            );
        }
    }

    /**
     * Resolve an active ticket.
     */
    public function resolve(
        Request $request,
        Ticket $ticket,
        TicketService $ticketService
    ): JsonResponse {

        Gate::authorize('resolve', $ticket);

        try {
            $ticket = $ticketService->resolveTicket(
                $ticket,
                $request->user()
            );

            return response()->json([
                'message' => 'Ticket resolved successfully.',
                'ticket' => $ticket,
            ]);

        } catch (Throwable $e) {

            return $this->handleError(
                $e,
                'resolve',
                $ticket->id
            );
        }
    }

    /**
     * Close a resolved ticket.
     */
    public function close(
        Request $request,
        Ticket $ticket,
        TicketService $ticketService
    ): JsonResponse {

        Gate::authorize('close', $ticket);

        try {
            $ticket = $ticketService->closeTicket(
                $ticket,
                $request->user()
            );

            return response()->json([
                'message' => 'Ticket closed successfully.',
                'ticket' => $ticket,
            ]);

        } catch (Throwable $e) {

            return $this->handleError(
                $e,
                'close',
                $ticket->id
            );
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
}
