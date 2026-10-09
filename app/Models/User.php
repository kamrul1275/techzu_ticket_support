<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Fields allowed for mass assignment
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    // Hide sensitive information from JSON responses
    protected $hidden = [
        'password',
        'remember_token',
    ];

    // Attribute type conversions
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Ticket Relationships
    |--------------------------------------------------------------------------
    */

    // Tickets created by this customer
    public function createdTickets(): HasMany
    {
        return $this->hasMany(
            Ticket::class,
            'customer_id'
        );
    }

    // Tickets currently assigned to this agent
    public function assignedTickets(): HasMany
    {
        return $this->hasMany(
            Ticket::class,
            'assigned_agent_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Message & Attachment Relationships
    |--------------------------------------------------------------------------
    */

    // Messages written by this user
    public function ticketMessages(): HasMany
    {
        return $this->hasMany(TicketMessage::class);
    }

    // Attachments uploaded by this user
    public function uploadedAttachments(): HasMany
    {
        return $this->hasMany(
            TicketAttachment::class,
            'uploaded_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Assignment Relationships
    |--------------------------------------------------------------------------
    */

    // Tickets assigned to this agent (history)
    public function assignmentHistory(): HasMany
    {
        return $this->hasMany(
            TicketAssignment::class,
            'agent_id'
        );
    }

    // Assignments performed by this user
    public function assignmentsMade(): HasMany
    {
        return $this->hasMany(
            TicketAssignment::class,
            'assigned_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Activity & Import Relationships
    |--------------------------------------------------------------------------
    */

    // Ticket activities performed by this user
    public function ticketActivities(): HasMany
    {
        return $this->hasMany(TicketActivity::class);
    }

    // CSV imports uploaded by this user
    public function ticketImports(): HasMany
    {
        return $this->hasMany(
            TicketImport::class,
            'uploaded_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Role Checking Methods
    |--------------------------------------------------------------------------
    */

    // Check if the user is an administrator
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    // Check if the user is a support agent
    public function isAgent(): bool
    {
        return $this->role === 'agent';
    }

    // Check if the user is a customer
    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }
}
