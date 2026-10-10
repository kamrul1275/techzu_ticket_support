<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\TicketEscalation;
use App\Models\User;
use App\Notifications\TicketSlaNotification;
use App\Services\TicketSlaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class CheckTicketSla extends Command
{
    protected $signature = 'tickets:check-sla';

    protected $description =
        'Check active tickets and escalate approaching or breached SLAs.';

    public function handle(TicketSlaService $slaService): int
    {
        $createdCount = 0;
        $errorCount = 0;

        // Only active tickets with an SLA deadline.
        Ticket::query()
            ->with('assignedAgent')
            ->whereNotNull('sla_due_at')
            ->whereNotIn('status', ['resolved', 'closed'])
            ->chunkById(100, function ($tickets) use (
                $slaService,
                &$createdCount,
                &$errorCount
            ) {

                foreach ($tickets as $ticket) {

                    try {

                        $status = $slaService->status($ticket);

                        // No escalation needed.
                        if (!in_array(
                            $status,
                            ['approaching', 'breached'],
                            true
                        )) {
                            continue;
                        }

                        $type = $status === 'approaching'
                            ? 'warning'
                            : 'breached';

                        // Store escalation and audit history atomically.
                        $created = DB::transaction(function () use (
                            $ticket,
                            $type
                        ) {

                            // Unique(ticket_id, type, sla_due_at)
                            // prevents duplicate escalations.
                            $escalation = TicketEscalation::firstOrCreate([
                                'ticket_id' => $ticket->id,
                                'type' => $type,
                                'sla_due_at' => $ticket->sla_due_at,
                            ]);

                            if (!$escalation->wasRecentlyCreated) {
                                return false;
                            }

                            $ticket->activities()->create([
                                'user_id' => null,
                                'action' => 'sla_' . $type,
                                'new_values' => [
                                    'sla_due_at' =>
                                        $ticket->sla_due_at->toDateTimeString(),
                                    'type' => $type,
                                ],
                            ]);

                            return true;

                        }, 3);

                        if (!$created) {
                            continue;
                        }

                        $createdCount++;

                        // Notify all admins and the assigned agent.
                        $recipients = User::query()
                            ->where('role', 'admin')
                            ->get();

                        if (
                            $ticket->assignedAgent &&
                            $ticket->assignedAgent->isAgent()
                        ) {
                            $recipients->push($ticket->assignedAgent);
                        }

                        $recipients = $recipients->unique('id');

                        if ($recipients->isNotEmpty()) {

                            Notification::send(
                                $recipients,
                                new TicketSlaNotification(
                                    $ticket->id,
                                    $ticket->ticket_number,
                                    $type
                                )
                            );
                        }

                    } catch (Throwable $e) {

                        $errorCount++;

                        Log::error('Ticket SLA escalation failed', [
                            'ticket_id' => $ticket->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

            });

        $this->info(
            "SLA check completed. New escalations: {$createdCount}. Errors: {$errorCount}."
        );

        return $errorCount === 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
