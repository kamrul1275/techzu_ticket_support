<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    protected $fillable = [
        'ticket_message_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    // Attachment belongs to a ticket
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    // Attachment may belong to a reply
    public function message(): BelongsTo
    {
        return $this->belongsTo(
            TicketMessage::class,
            'ticket_message_id'
        );
    }

    // User who uploaded this attachment
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by'
        );
    }
}