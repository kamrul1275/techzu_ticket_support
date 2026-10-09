<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    // Who can view ticket lists?
    public function viewAny(User $user): bool
    {
        return $user->isAdmin()
            || $user->isAgent()
            || $user->isCustomer();
    }

    // Who can view a specific ticket?
    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgent()) {
            return $ticket->assigned_agent_id === $user->id;
        }

        return $user->isCustomer()
            && $ticket->customer_id === $user->id;
    }

    // Who can create tickets?
    public function create(User $user): bool
    {
        return $user->isCustomer();
    }

    // Who can update tickets?
    public function update(User $user, Ticket $ticket): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isAgent()) {
            return $ticket->assigned_agent_id === $user->id
                && !in_array($ticket->status, ['resolved', 'closed'], true);
        }

        return $user->isCustomer()
            && $ticket->customer_id === $user->id
            && $ticket->status === 'open';
    }

    // Who can resolve tickets?
    public function resolve(User $user, Ticket $ticket): bool
    {
        if (in_array($ticket->status, ['resolved', 'closed'], true)) {
            return false;
        }

        return $user->isAdmin()
            || (
                $user->isAgent()
                && $ticket->assigned_agent_id === $user->id
            );
    }

    // Who can close tickets?
    public function close(User $user, Ticket $ticket): bool
    {
        if ($ticket->status !== 'resolved') {
            return false;
        }

        return $user->isAdmin()
            || (
                $user->isCustomer()
                && $ticket->customer_id === $user->id
            );
    }

    // Who can assign tickets manually?
    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            && !in_array($ticket->status, ['resolved', 'closed'], true);
    }

    // Who can accept unassigned tickets?
    public function accept(User $user, Ticket $ticket): bool
    {
        return $user->isAgent()
            && $ticket->assigned_agent_id === null
            && !in_array($ticket->status, ['resolved', 'closed'], true);
    }

    // Who can reply to tickets?
    public function reply(User $user, Ticket $ticket): bool
    {
        return $ticket->status !== 'closed'
            && $this->view($user, $ticket);
    }

    // Who can add internal notes?
    public function addInternalNote(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin()
            || (
                $user->isAgent()
                && $ticket->assigned_agent_id === $user->id
            );
    }

    // Prevent permanent deletion
    public function delete(User $user, Ticket $ticket): bool
    {
        return false;
    }
}