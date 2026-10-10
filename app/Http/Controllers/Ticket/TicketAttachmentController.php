<?php

namespace App\Http\Controllers\Ticket;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TicketAttachmentController extends Controller
{
    /**
     * Download an attachment securely.
     */
    public function download(
        Request $request,
        Ticket $ticket,
        TicketAttachment $attachment
    ): StreamedResponse {

        // Check access to the parent ticket
        Gate::authorize('view', $ticket);

        // Attachment must belong to this ticket
        abort_unless(
            (int) $attachment->ticket_id === (int) $ticket->id,
            404
        );

        // Customer cannot download internal note files
        if ($request->user()->isCustomer()) {

            $message = $attachment->message;

            abort_if(
                !$message || $message->is_internal,
                403
            );
        }

        // Prevent downloading a missing file
        abort_unless(
            Storage::disk($attachment->disk)->exists($attachment->path),
            404
        );

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_name
        );
    }
}