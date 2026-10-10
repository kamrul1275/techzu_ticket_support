<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TicketService
{
    /**
     * Create a new support ticket.
     */
    public function createTicket(User $user, array $data): Ticket
    {
        return DB::transaction(function () use ($user, $data) {

            // 1. Create ticket for the authenticated customer
            $ticket = $user->createdTickets()->create($data);

          
        // 2. Set initial status and generate ticket number
        $year = now('Asia/Dhaka')->year;

        $ticket->status = 'open';

        $ticket->ticket_number = sprintf(
            'TKT-%d-%06d',
            $year,
            $ticket->id
        );

        $ticket->save();

            // 3. Save activity history
            $ticket->activities()->create([
                'user_id' => $user->id,
                'action' => 'created',
                'new_values' => [
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                ],
            ]);

            return $ticket->load('category');
        });
    }

    /**
     * Update ticket information.
     */
    public function updateTicket(
        Ticket $ticket,
        User $user,
        array $data
    ): Ticket {

        return DB::transaction(function () use ($ticket, $user, $data) {

            // 1. Remember previous values
            $oldValues = $ticket->only(array_keys($data));

            // 2. Set validated fields
            $ticket->fill($data);

            // 3. Find which fields actually changed
            $changes = $ticket->getDirty();

            if (empty($changes)) {
                return $ticket;
            }

            // 4. Save updated ticket
            $ticket->save();

            // 5. Save activity history
            $ticket->activities()->create([
                'user_id' => $user->id,
                'action' => 'updated',
                'old_values' => array_intersect_key(
                    $oldValues,
                    $changes
                ),
                'new_values' => $changes,
            ]);

            return $ticket->load('category');
        });
    }

    /**
     * Mark a ticket as resolved.
     */
    public function resolveTicket(
        Ticket $ticket,
        User $user
    ): Ticket {

        return DB::transaction(function () use ($ticket, $user) {

            $oldStatus = $ticket->status;

            // Update ticket status
            $ticket->status = 'resolved';
            $ticket->resolved_at = now();

            $ticket->save();

            // Save activity
            $ticket->activities()->create([
                'user_id' => $user->id,
                'action' => 'resolved',
                'old_values' => [
                    'status' => $oldStatus,
                ],
                'new_values' => [
                    'status' => 'resolved',
                ],
            ]);

            return $ticket;
        });
    }

    /**
     * Close a resolved ticket.
     */
    public function closeTicket(
        Ticket $ticket,
        User $user
    ): Ticket {

        return DB::transaction(function () use ($ticket, $user) {

            $oldStatus = $ticket->status;

            // Update ticket status
            $ticket->status = 'closed';
            $ticket->closed_at = now();

            $ticket->save();

            // Save activity
            $ticket->activities()->create([
                'user_id' => $user->id,
                'action' => 'closed',
                'old_values' => [
                    'status' => $oldStatus,
                ],
                'new_values' => [
                    'status' => 'closed',
                ],
            ]);

            return $ticket;
        });
    }










      /**
     * Assign a ticket to a support agent.
     */
    public function assignTicket(
        Ticket $ticket,
        User $admin,
        int $agentId
    ): Ticket {

        return DB::transaction(function () use ($ticket, $admin, $agentId) {

            // Lock ticket to prevent simultaneous changes
            $ticket = Ticket::whereKey($ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Do not assign completed tickets
            if (in_array($ticket->status, ['resolved', 'closed'], true)) {
                throw ValidationException::withMessages([
                    'agent_id' => 'Completed tickets cannot be assigned.',
                ]);
            }

            // Find selected support agent
            $agent = User::where('role', 'agent')
                ->findOrFail($agentId);

// Avoid duplicate assignment history
if (
    $ticket->assigned_agent_id !== null &&
    (int) $ticket->assigned_agent_id === (int) $agent->id
) {
    return $ticket;
}

            $previousAgentId = $ticket->assigned_agent_id;

            // Update current assigned agent
            $ticket->assigned_agent_id = $agent->id;
            $ticket->save();

            // Save assignment history
            $ticket->assignments()->create([
                'agent_id' => $agent->id,
                'assigned_by' => $admin->id,
                'method' => 'manual',
            ]);

            // Save ticket activity
            $ticket->activities()->create([
                'user_id' => $admin->id,
                'action' => 'assigned',
                'old_values' => [
                    'assigned_agent_id' => $previousAgentId,
                ],
                'new_values' => [
                    'assigned_agent_id' => $agent->id,
                ],
            ]);

            return $ticket;
        });
    }
  /**
     * Allow an agent to accept an unassigned ticket.
     */
    public function acceptTicket(
        Ticket $ticket,
        User $agent
    ): Ticket {

        return DB::transaction(function () use ($ticket, $agent) {

            // 1. Lock the latest ticket row
            $ticket = Ticket::whereKey($ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 2. Make sure ticket is still available
            if (
                $ticket->status !== 'open' ||
                $ticket->assigned_agent_id !== null
            ) {
                throw ValidationException::withMessages([
                    'ticket' => 'This ticket is no longer available.',
                ]);
            }

            // 3. Assign ticket to current agent
            $ticket->assigned_agent_id = $agent->id;
            $ticket->save();

            // 4. Save assignment history
            $ticket->assignments()->create([
                'agent_id' => $agent->id,
                'assigned_by' => $agent->id,
                'method' => 'manual',
            ]);

            // 5. Save activity history
            $ticket->activities()->create([
                'user_id' => $agent->id,
                'action' => 'accepted',
                'old_values' => [
                    'assigned_agent_id' => null,
                ],
                'new_values' => [
                    'assigned_agent_id' => $agent->id,
                ],
            ]);

            return $ticket;
        });
    }
 /**
     * Automatically assign an open ticket
     * to the agent with the least active tickets.
     */
    public function autoAssignTicket(
        Ticket $ticket,
        User $admin
    ): Ticket {

        return DB::transaction(function () use ($ticket, $admin) {

            // 1. Only an admin can auto-assign
            if (!$admin->isAdmin()) {
                throw ValidationException::withMessages([
                    'auto_assign' => 'Only admins can automatically assign tickets.',
                ]);
            }

            // 2. Lock ticket and read its latest state
            $ticket = Ticket::whereKey($ticket->id)
                ->lockForUpdate()
                ->firstOrFail();

            // 3. Ticket must be open and unassigned
            if (
                $ticket->status !== 'open' ||
                $ticket->assigned_agent_id !== null
            ) {
                throw ValidationException::withMessages([
                    'auto_assign' => 'This ticket is no longer available for automatic assignment.',
                ]);
            }

            // 4. Lock agent rows to serialize auto-assignment
            // requests across different tickets
            $agents = User::where('role', 'agent')
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id', 'name']);

            if ($agents->isEmpty()) {
                throw ValidationException::withMessages([
                    'auto_assign' => 'No support agents are available.',
                ]);
            }

            // 5. Count active tickets for each agent
            $workloads = Ticket::query()
                ->select('assigned_agent_id')
                ->selectRaw('COUNT(*) AS ticket_count')
                ->whereIn('assigned_agent_id', $agents->pluck('id'))
                ->whereNotIn('status', ['resolved', 'closed'])
                ->groupBy('assigned_agent_id')
                ->pluck('ticket_count', 'assigned_agent_id');

            // 6. Select agent with lowest workload
            $selectedAgent = null;
            $lowestCount = PHP_INT_MAX;

            foreach ($agents as $agent) {

                $activeCount = (int) ($workloads[$agent->id] ?? 0);

                if ($activeCount < $lowestCount) {
                    $lowestCount = $activeCount;
                    $selectedAgent = $agent;
                }
            }

            // 7. Update ticket assignment
            $ticket->assigned_agent_id = $selectedAgent->id;
            $ticket->save();

            // 8. Save assignment history
            $ticket->assignments()->create([
                'agent_id' => $selectedAgent->id,
                'assigned_by' => $admin->id,
                'method' => 'auto',
            ]);

            // 9. Save activity history
            $ticket->activities()->create([
                'user_id' => $admin->id,
                'action' => 'auto_assigned',
                'old_values' => [
                    'assigned_agent_id' => null,
                ],
                'new_values' => [
                    'assigned_agent_id' => $selectedAgent->id,
                ],
            ]);

            return $ticket->load('assignedAgent');

        }, 3);
    }
}


