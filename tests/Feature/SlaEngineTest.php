<?php

namespace Tests\Feature;

use App\Models\BusinessHoliday;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketEscalation;
use App\Models\User;
use App\Notifications\TicketSlaNotification;
use App\Services\SlaClock;
use App\Services\TicketService;
use App\Services\TicketSlaService;
use Carbon\CarbonImmutable;
use Database\Seeders\SupportSettingsSeeder;
use Database\Seeders\TicketCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SlaEngineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Prepare SLA policies, business hours and categories
        $this->seed([
            SupportSettingsSeeder::class,
            TicketCategorySeeder::class,
        ]);
    }

    /**
     * Create a real ticket for testing.
     */
    private function createTicket(
        User $customer,
        string $priority = 'critical'
    ): Ticket {

        $category = TicketCategory::query()
            ->where('is_active', true)
            ->firstOrFail();

        return app(TicketService::class)->createTicket(
            $customer,
            [
                'category_id' => $category->id,
                'subject' => 'SLA test ticket',
                'description' => 'Testing SLA calculation.',
                'priority' => $priority,
            ]
        );
    }

    /**
     * Test 1:
     * SLA must skip Friday and Saturday.
     *
     * Thursday 5 PM + 2 working hours
     * = Sunday 10 AM.
     */
    public function test_sla_skips_weekend(): void
    {
        $start = CarbonImmutable::parse(
            '2026-10-08 17:00:00',
            'Asia/Dhaka'
        );

        $deadline = app(SlaClock::class)
            ->addWorkingMinutes($start, 120);

        $this->assertSame(
            '2026-10-11 10:00',
            $deadline
                ->setTimezone('Asia/Dhaka')
                ->format('Y-m-d H:i')
        );
    }

    /**
     * Test 2:
     * SLA must skip configured business holidays.
     */
    public function test_sla_skips_business_holiday(): void
    {
        BusinessHoliday::create([
            'holiday_date' => '2026-10-12',
            'name' => 'Test Holiday',
        ]);

        $start = CarbonImmutable::parse(
            '2026-10-11 17:00:00',
            'Asia/Dhaka'
        );

        // Sunday: 1 working hour
        // Monday: Holiday
        // Tuesday: Remaining 2 working hours
        $deadline = app(SlaClock::class)
            ->addWorkingMinutes($start, 180);

        $this->assertSame(
            '2026-10-13 11:00',
            $deadline
                ->setTimezone('Asia/Dhaka')
                ->format('Y-m-d H:i')
        );
    }

    /**
     * Test 3:
     * Creating a ticket should automatically
     * calculate the SLA deadline.
     */
    public function test_ticket_creation_sets_sla_deadline(): void
    {
        $createdAt = CarbonImmutable::parse(
            '2026-10-08 17:00:00',
            'Asia/Dhaka'
        );

        // Freeze time
        $this->travelTo($createdAt);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $ticket = $this->createTicket(
            $customer,
            'critical'
        );

        $ticket->refresh();

        $this->assertNotNull($ticket->sla_due_at);

        $this->assertSame(
            '2026-10-11 10:00',
            $ticket->sla_due_at
                ->copy()
                ->setTimezone('Asia/Dhaka')
                ->format('Y-m-d H:i')
        );

        $this->travelBack();
    }

    /**
     * Test 4:
     * Verify Within, Approaching and Breached statuses.
     *
     * Uses a real database ticket to ensure
     * consistent Eloquent datetime casting.
     */
    public function test_sla_status_changes_over_time(): void
    {
        // Step 1: Create ticket on Thursday at 5 PM
        $this->travelTo(CarbonImmutable::parse(
            '2026-10-08 17:00:00',
            'Asia/Dhaka'
        ));

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $ticket = $this->createTicket(
            $customer,
            'critical'
        );

        // Step 2: Configure 15-minute warning threshold
        $updated = SlaPolicy::query()
            ->where('priority', 'critical')
            ->update([
                'warning_before_minutes' => 15,
            ]);

        $this->assertSame(1, $updated);

        // Step 3: Reload ticket from database
        $ticket->refresh();

        // Confirm stored deadline
        $this->assertSame(
            '2026-10-11 10:00',
            $ticket->sla_due_at
                ->copy()
                ->setTimezone('Asia/Dhaka')
                ->format('Y-m-d H:i')
        );

        $slaService = app(TicketSlaService::class);

        // Step 4: Sunday 9:30 AM
        // 30 working minutes remaining
        // Expected: Within SLA
        $withinTime = CarbonImmutable::parse(
            '2026-10-11 09:30:00',
            'Asia/Dhaka'
        );

        $this->assertSame(
            'within',
            $slaService->status(
                $ticket,
                $withinTime
            )
        );

        // Step 5: Sunday 9:50 AM
        // 10 working minutes remaining
        // Expected: Approaching Breach
        $approachingTime = CarbonImmutable::parse(
            '2026-10-11 09:50:00',
            'Asia/Dhaka'
        );

        $this->assertSame(
            'approaching',
            $slaService->status(
                $ticket,
                $approachingTime
            )
        );

        // Step 6: Sunday 10:01 AM
        // SLA deadline exceeded
        // Expected: Breached
        $breachedTime = CarbonImmutable::parse(
            '2026-10-11 10:01:00',
            'Asia/Dhaka'
        );

        $this->assertSame(
            'breached',
            $slaService->status(
                $ticket,
                $breachedTime
            )
        );

        $this->travelBack();
    }

    /**
     * Test 5:
     * Scheduler must not create duplicate SLA escalations.
     */
    public function test_sla_escalation_is_not_duplicated(): void
    {
        Notification::fake();

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        // Thursday 5 PM: Create critical ticket
        $this->travelTo(CarbonImmutable::parse(
            '2026-10-08 17:00:00',
            'Asia/Dhaka'
        ));

        $ticket = $this->createTicket(
            $customer,
            'critical'
        );

        // Sunday 9:50 AM: Approaching SLA deadline
        $this->travelTo(CarbonImmutable::parse(
            '2026-10-11 09:50:00',
            'Asia/Dhaka'
        ));

        // First scheduler execution
        $this->artisan('tickets:check-sla')
            ->assertExitCode(0);

        // Second execution must not duplicate warning
        $this->artisan('tickets:check-sla')
            ->assertExitCode(0);

        $this->assertDatabaseHas('ticket_escalations', [
            'ticket_id' => $ticket->id,
            'type' => 'warning',
        ]);

        // Exactly one warning record
        $this->assertSame(
            1,
            TicketEscalation::where(
                'ticket_id',
                $ticket->id
            )->count()
        );

        // Sunday 10:01 AM: SLA breached
        $this->travelTo(CarbonImmutable::parse(
            '2026-10-11 10:01:00',
            'Asia/Dhaka'
        ));

        // Run scheduler again
        $this->artisan('tickets:check-sla')
            ->assertExitCode(0);

        $this->assertDatabaseHas('ticket_escalations', [
            'ticket_id' => $ticket->id,
            'type' => 'breached',
        ]);

        // Exactly two records:
        // 1 warning + 1 breach
        $this->assertSame(
            2,
            TicketEscalation::where(
                'ticket_id',
                $ticket->id
            )->count()
        );

        // Verify notification sent to admin
        Notification::assertSentTo(
            $admin,
            TicketSlaNotification::class
        );

        $this->travelBack();
    }

    /**
     * Test 6:
     * Ticket resolved before deadline
     * must have SLA Met status.
     */
    public function test_resolved_ticket_can_meet_sla(): void
    {
        $ticket = new Ticket();

        $ticket->priority = 'critical';
        $ticket->status = 'resolved';

        $ticket->sla_due_at = CarbonImmutable::parse(
            '2026-10-11 10:00:00',
            'Asia/Dhaka'
        );

        $ticket->resolved_at = CarbonImmutable::parse(
            '2026-10-11 09:30:00',
            'Asia/Dhaka'
        );

        $this->assertSame(
            'met',
            app(TicketSlaService::class)->status($ticket)
        );
    }
}
