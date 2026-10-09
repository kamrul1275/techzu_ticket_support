<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Check permission to update this ticket.
     */
    public function authorize(): bool
    {
        $ticket = $this->route('ticket');

        return Gate::allows('update', $ticket);
    }

    /**
     * Validate updated fields.
     */
    public function rules(): array
    {
        return [
            'category_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('ticket_categories', 'id')
                    ->where('is_active', true),
            ],

            'subject' => [
                'sometimes',
                'required',
                'string',
                'max:200',
            ],

            'description' => [
                'sometimes',
                'required',
                'string',
            ],

            'priority' => [
                'sometimes',
                Rule::prohibitedIf(
                    $this->user()->isCustomer()
                ),
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