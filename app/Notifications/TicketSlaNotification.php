<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketSlaNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $ticketId,
        public string $ticketNumber,
        public string $type
    ) {}

    /**
     * Send notification through email.
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build email content.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $isBreached = $this->type === 'breached';

        $subject = $isBreached
            ? "SLA Breached: {$this->ticketNumber}"
            : "SLA Warning: {$this->ticketNumber}";

        $message = $isBreached
            ? 'This support ticket has exceeded its SLA deadline.'
            : 'This support ticket is approaching its SLA deadline.';

        return (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},")
            ->line($message)
            ->line("Ticket: {$this->ticketNumber}")
            ->action(
                'View Ticket',
                route('tickets.show', $this->ticketId)
            )
            ->line('Please review this ticket as soon as possible.');
    }
}
