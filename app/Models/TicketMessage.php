<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    protected $fillable = [
        'user_id',
        'body',
        'is_internal',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
        ];
    }

    // This message belongs to one ticket
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    // This message was sent by one user
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // A message can have multiple attachments
    public function attachments(): HasMany
    {
        return $this->hasMany(
            TicketAttachment::class,
            'ticket_message_id'
        );
    }
}