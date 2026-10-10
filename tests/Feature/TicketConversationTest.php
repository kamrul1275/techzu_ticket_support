<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketCategory;
use App\Models\User;
use App\Services\TicketConversationService;
use App\Services\TicketService;
use Database\Seeders\TicketCategorySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TicketConversationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(TicketCategorySeeder::class);
    }

    private function makeTicket(User $customer): Ticket
    {
        $category = TicketCategory::where('is_active', true)
            ->firstOrFail();

        return app(TicketService::class)->createTicket(
            $customer,
            [
                'category_id' => $category->id,
                'subject' => 'Login issue',
                'description' => 'I cannot access my account.',
                'priority' => 'medium',
            ]
        );
    }

    /**
     * Customer can send a public reply.
     */
    public function test_customer_can_reply(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $ticket = $this->makeTicket($customer);

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), [
                'type' => 'reply',
                'body' => 'I still need help.',
            ])
            ->assertRedirect(route('tickets.show', $ticket));

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $customer->id,
            'body' => 'I still need help.',
            'is_internal' => false,
        ]);
    }

    /**
     * Assigned agent can reply to a ticket.
     */
    public function test_assigned_agent_can_reply(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $agent = User::factory()->create([
            'role' => 'agent',
        ]);

        $ticket = $this->makeTicket($customer);

        app(TicketService::class)->acceptTicket($ticket, $agent);

        $this->actingAs($agent)
            ->post(route('tickets.messages.store', $ticket), [
                'type' => 'reply',
                'body' => 'We are investigating.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'body' => 'We are investigating.',
            'is_internal' => false,
        ]);
    }

    /**
     * Internal notes must not be shown to customers.
     */
    public function test_internal_note_is_hidden_from_customer(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $ticket = $this->makeTicket($customer);

        $this->actingAs($admin)
            ->post(route('tickets.messages.store', $ticket), [
                'type' => 'note',
                'body' => 'Secret internal investigation.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'body' => 'Secret internal investigation.',
            'is_internal' => true,
        ]);

        // Staff can see the internal note
        $this->actingAs($admin)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertSee('Secret internal investigation.');

        // Customer must not see the note
        $this->actingAs($customer)
            ->get(route('tickets.show', $ticket))
            ->assertOk()
            ->assertDontSee('Secret internal investigation.');
    }

    /**
     * Customers cannot create internal notes.
     */
    public function test_customer_cannot_add_internal_note(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $ticket = $this->makeTicket($customer);

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), [
                'type' => 'note',
                'body' => 'Attempted private note.',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('ticket_messages', 0);
    }

    /**
     * Uploaded attachments are private and downloadable.
     */
    public function test_customer_can_upload_and_download_attachment(): void
    {
        Storage::fake('local');

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $ticket = $this->makeTicket($customer);

        $file = UploadedFile::fake()->create(
            'proof.pdf',
            100,
            'application/pdf'
        );

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), [
                'type' => 'reply',
                'body' => 'Please check the attached document.',
                'attachments' => [$file],
            ])
            ->assertRedirect();

        $attachment = TicketAttachment::firstOrFail();

        Storage::disk('local')->assertExists($attachment->path);

        $this->assertDatabaseHas('ticket_attachments', [
            'ticket_id' => $ticket->id,
            'uploaded_by' => $customer->id,
            'original_name' => 'proof.pdf',
        ]);

        $this->actingAs($customer)
            ->get(route('tickets.attachments.download', [
                $ticket,
                $attachment,
            ]))
            ->assertOk()
            ->assertDownload('proof.pdf');
    }

    /**
     * Customer cannot download internal note attachments.
     */
    public function test_customer_cannot_download_internal_attachment(): void
    {
        Storage::fake('local');

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $ticket = $this->makeTicket($customer);

        app(TicketConversationService::class)->addMessage(
            $ticket,
            $admin,
            'Staff-only document.',
            true,
            [
                UploadedFile::fake()->create(
                    'internal.pdf',
                    50,
                    'application/pdf'
                ),
            ]
        );

        $attachment = TicketAttachment::firstOrFail();

        $this->actingAs($customer)
            ->get(route('tickets.attachments.download', [
                $ticket,
                $attachment,
            ]))
            ->assertForbidden();
    }

    /**
     * Dangerous file types must be rejected.
     */
    public function test_invalid_attachment_type_is_rejected(): void
    {
        Storage::fake('local');

        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $ticket = $this->makeTicket($customer);

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), [
                'type' => 'reply',
                'body' => 'Test upload',
                'attachments' => [
                    UploadedFile::fake()->create(
                        'malware.exe',
                        10,
                        'application/octet-stream'
                    ),
                ],
            ])
            ->assertSessionHasErrors('attachments.0');

        $this->assertDatabaseCount('ticket_messages', 0);
        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    /**
     * Completed tickets must not accept new replies.
     */
    public function test_closed_ticket_cannot_receive_reply(): void
    {
        $customer = User::factory()->create([
            'role' => 'customer',
        ]);

        $ticket = $this->makeTicket($customer);

        $ticket->status = 'closed';
        $ticket->save();

        $this->actingAs($customer)
            ->post(route('tickets.messages.store', $ticket), [
                'type' => 'reply',
                'body' => 'Reply after closing.',
            ])
            ->assertForbidden();
    }
}