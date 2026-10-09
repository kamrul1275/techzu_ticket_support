<?php

namespace App\Http\Requests;

use App\Models\Ticket;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreTicketRequest extends FormRequest
{
    /**
     * Check permission to create a ticket.
     */
    public function authorize(): bool
    {
        return Gate::allows('create', Ticket::class);
    }

    /**
     * Validate ticket data.
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('ticket_categories', 'id')
                    ->where('is_active', true),
            ],

            'subject' => [
                'required',
                'string',
                'max:200',
            ],

            'description' => [
                'required',
                'string',
            ],

            'priority' => [
                'required',
                Rule::in([
                    'low',
                    'medium',
                    'high',
                    'critical',
                ]),
            ],
        ];
    }
}