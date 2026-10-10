<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class TicketConversationService
{
    /**
     * Save a public reply or internal staff note.
     * Files are stored privately.
     */
    public function addMessage(
        Ticket $ticket,
        User $user,
        string $body,
        bool $isInternal,
        array $files = []
    ): TicketMessage {

        $storedPaths = [];

        try {
            return DB::transaction(function () use (
                $ticket,
                $user,
                $body,
                $isInternal,
                $files,
                &$storedPaths
            ) {

                // Read latest ticket state under a row lock
                $ticket = Ticket::whereKey($ticket->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                // Do not accept replies on completed tickets
                if (in_array($ticket->status, ['resolved', 'closed'], true)) {
                    throw ValidationException::withMessages([
                        'body' => 'You cannot reply to a completed ticket.',
                    ]);
                }

                // Recheck permissions against the latest ticket
                $isAdmin = $user->isAdmin();

                $isAssignedAgent = $user->isAgent()
                    && $ticket->assigned_agent_id !== null
                    && (int) $ticket->assigned_agent_id === (int) $user->id;

                $isOwner = $user->isCustomer()
                    && (int) $ticket->customer_id === (int) $user->id;

                $allowed = $isInternal
                    ? ($isAdmin || $isAssignedAgent)
                    : ($isAdmin || $isAssignedAgent || $isOwner);

                if (!$allowed) {
                    throw ValidationException::withMessages([
                        'body' => 'You are not allowed to reply to this ticket.',
                    ]);
                }

                // 1. Save message
                $message = $ticket->messages()->create([
                    'user_id' => $user->id,
                    'body' => $body,
                    'is_internal' => $isInternal,
                ]);

                // 2. Save attachments
                foreach ($files as $file) {

                    $path = $file->store(
                        "ticket-attachments/{$ticket->id}",
                        'local'
                    );

                    if (!$path) {
                        throw new \RuntimeException('File storage failed.');
                    }

                    $storedPaths[] = $path;

                    $attachment = new TicketAttachment([
                        'ticket_message_id' => $message->id,
                        'disk' => 'local',
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'mime_type' => $file->getMimeType(),
                        'size_bytes' => $file->getSize(),
                    ]);

                    $attachment->uploaded_by = $user->id;

                    $ticket->attachments()->save($attachment);
                }

                // 3. Record activity (without exposing note body)
                $ticket->activities()->create([
                    'user_id' => $user->id,
                    'action' => $isInternal
                        ? 'internal_note_added'
                        : 'replied',
                    'new_values' => [
                        'message_id' => $message->id,
                        'is_internal' => $isInternal,
                    ],
                ]);

                return $message;
            });

        } catch (Throwable $e) {

            // Filesystem does not support DB rollback.
            // Remove files if the database transaction fails.
            if (!empty($storedPaths)) {
                Storage::disk('local')->delete($storedPaths);
            }

            throw $e;
        }
    }
}