<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketService;
use Database\Seeders\TicketCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAssignmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Prepare ticket categories in the test database
        $this->seed(TicketCategorySeeder::class);
    }

    /**
     * Create an open ticket for testing.
     */
    private function makeTicket(User $customer): Ticket
    {
        $category = TicketCategory::query()
            ->where('is_active', true)
            ->firstOrFail();

        return app(TicketService::class)->createTicket(
            $customer,
            [
                'category_id' => $category->id,
                'subject' => 'Test support request',
                'description' => 'Testing ticket assignments.',
                'priority' => 'medium',
            ]
        );
    }

    /**
     * Test 1: An agent can accept an available ticket.
     */
    public function test_agent_can_accept_ticket(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $agent = User::factory()->create([
            'role' => 'agent',
        ]);

        $ticket = $this->makeTicket($customer);

        $this->actingAs($agent)
            ->patch(route('tickets.accept', $ticket))
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_agent_id' => $agent->id,
        ]);

        $this->assertDatabaseHas('ticket_assignments', [
            'ticket_id' => $ticket->id,
            'agent_id' => $agent->id,
            'assigned_by' => $agent->id,
        ]);

        $this->assertDatabaseHas('ticket_activities', [
            'ticket_id' => $ticket->id,
            'action' => 'accepted',
        ]);
    }

    /**
     * Test 2: A second agent cannot accept the same ticket.
     */
    public function test_second_agent_cannot_accept_taken_ticket(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $agentA = User::factory()->create([
            'role' => 'agent',
        ]);

        $agentB = User::factory()->create([
            'role' => 'agent',
        ]);

        $ticket = $this->makeTicket($customer);

        // First agent accepts successfully
        $this->actingAs($agentA)
            ->patch(route('tickets.accept', $ticket))
            ->assertRedirect();

        // Second agent is rejected
        $this->actingAs($agentB)
            ->patch(route('tickets.accept', $ticket))
            ->assertForbidden();

        // Ticket is still assigned to the first agent
        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_agent_id' => $agentA->id,
        ]);

        // Only one assignment history record
        $this->assertDatabaseCount('ticket_assignments', 1);
    }

    /**
     * Test 3: A customer cannot accept a ticket.
     */
    public function test_customer_cannot_accept_ticket(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $ticket = $this->makeTicket($customer);

        $this->actingAs($customer)
            ->patch(route('tickets.accept', $ticket))
            ->assertForbidden();

        $this->assertNull(
            $ticket->fresh()->assigned_agent_id
        );
    }

    /**
     * Test 4: An agent cannot use admin manual assignment.
     */
    public function test_agent_cannot_manually_assign_ticket(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $agent = User::factory()->create([
            'role' => 'agent',
        ]);

        $ticket = $this->makeTicket($customer);

        $this->actingAs($agent)
            ->patch(route('tickets.assign', $ticket), [
                'agent_id' => $agent->id,
            ])
            ->assertForbidden();
    }

    /**
     * Test 5: Reassigning to the same agent
     * must not create duplicate history.
     */
    public function test_same_agent_assignment_is_not_duplicated(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $agent = User::factory()->create([
            'role' => 'agent',
        ]);

        $ticket = $this->makeTicket($customer);

        // First assignment
        $this->actingAs($admin)
            ->patch(route('tickets.assign', $ticket), [
                'agent_id' => $agent->id,
            ])
            ->assertRedirect();

        // Same assignment again
        $this->actingAs($admin)
            ->patch(route('tickets.assign', $ticket), [
                'agent_id' => $agent->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('ticket_assignments', 1);
    }

    /**
     * Test 6: Auto assignment selects the
     * agent with fewer active tickets.
     */
    public function test_auto_assignment_uses_least_workload(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $busyAgent = User::factory()->create([
            'role' => 'agent',
        ]);

        $freeAgent = User::factory()->create([
            'role' => 'agent',
        ]);

        // Give one active ticket to the busy agent
        $busyTicket = $this->makeTicket($customer);

        app(TicketService::class)->assignTicket(
            $busyTicket,
            $admin,
            $busyAgent->id
        );

        // New ticket awaiting automatic assignment
        $newTicket = $this->makeTicket($customer);

        $this->actingAs($admin)
            ->patch(route('tickets.auto-assign', $newTicket))
            ->assertRedirect(route('tickets.show', $newTicket));

        // Agent with zero tickets should be selected
        $this->assertDatabaseHas('tickets', [
            'id' => $newTicket->id,
            'assigned_agent_id' => $freeAgent->id,
        ]);

        $this->assertDatabaseHas('ticket_assignments', [
            'ticket_id' => $newTicket->id,
            'agent_id' => $freeAgent->id,
            'method' => 'auto',
        ]);
    }
}