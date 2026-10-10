<?php

namespace App\Services;

use App\Models\SlaPolicy;
use App\Models\Ticket;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class TicketSlaService
{
    public function __construct(
        private SlaClock $clock
    ) {}

    /**
     * Calculate a deadline for a ticket priority.
     */
    public function deadline(
        string $priority,
        CarbonInterface $createdAt
    ): CarbonImmutable {

        $policy = SlaPolicy::query()
            ->where('priority', $priority)
            ->where('is_active', true)
            ->first();

        if (!$policy) {
            throw ValidationException::withMessages([
                'priority' => 'No active SLA policy found for this priority.',
            ]);
        }

        return $this->clock->addWorkingMinutes(
            $createdAt,
            (int) $policy->resolution_minutes
        );
    }

    /**
     * Calculate current ticket SLA status.
     *
     * Returns:
     * not_set, within, approaching, breached, met
     */
    public function status(
        Ticket $ticket,
        ?CarbonInterface $asOf = null
    ): string {

        if (!$ticket->sla_due_at) {
            return 'not_set';
        }

        $dueAt = CarbonImmutable::instance(
            $ticket->sla_due_at
        );

        // Completed tickets use actual resolution time.
        if (in_array($ticket->status, ['resolved', 'closed'], true)) {

            $completedAt = $ticket->resolved_at
                ?? $ticket->closed_at;

            if (!$completedAt) {
                return 'not_set';
            }

            return $completedAt->lessThanOrEqualTo($dueAt)
                ? 'met'
                : 'breached';
        }

        $currentTime = CarbonImmutable::instance(
            $asOf ?? now()
        );

        if ($currentTime->greaterThanOrEqualTo($dueAt)) {
            return 'breached';
        }

        $policy = SlaPolicy::query()
            ->where('priority', $ticket->priority)
            ->first();

        $warningMinutes = (int) (
            $policy?->warning_before_minutes ?? 30
        );

        // Calculate remaining WORKING time, not calendar time.
        $remainingSeconds = $this->clock->workingSecondsBetween(
            $currentTime,
            $dueAt
        );

        return $remainingSeconds <= $warningMinutes * 60
            ? 'approaching'
            : 'within';
    }
}
