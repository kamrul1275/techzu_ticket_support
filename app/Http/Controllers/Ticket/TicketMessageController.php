<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketConversationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class TicketMessageController extends Controller
{
    /**
     * Store a public reply or internal note.
     */
    public function store(
        Request $request,
        Ticket $ticket,
        TicketConversationService $conversationService
    ): RedirectResponse {

        // Validate message and uploaded files
        $data = $request->validate([
            'type' => [
                'required',
                Rule::in(['reply', 'note']),
            ],
            'body' => [
                'required',
                'string',
                'max:5000',
            ],
            'attachments' => [
                'nullable',
                'array',
                'max:3',
            ],
            'attachments.*' => [
                'required',
                'file',
                'mimes:jpg,jpeg,png,pdf,txt',
                'max:5120',
            ],
        ]);

        $isInternal = $data['type'] === 'note';

        // Authorize the correct action
        if ($isInternal) {
            Gate::authorize('addInternalNote', $ticket);
        } else {
            Gate::authorize('reply', $ticket);
        }

        try {
            $conversationService->addMessage(
                $ticket,
                $request->user(),
                $data['body'],
                $isInternal,
                $request->file('attachments', [])
            );

            return redirect()
                ->route('tickets.show', $ticket)
                ->with(
                    'success',
                    $isInternal
                        ? 'Internal note added successfully.'
                        : 'Reply sent successfully.'
                );

        } catch (ValidationException $e) {
            throw $e;

        } catch (Throwable $e) {

            Log::error('Ticket message creation failed', [
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withInput($request->except('attachments'))
                ->with('error', 'Unable to save your message.');
        }
    }
}