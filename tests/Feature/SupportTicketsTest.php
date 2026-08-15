<?php

namespace Tests\Feature;

use App\Enums\TicketAuthorType;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Filament\Pages\AdminNotifications;
use App\Filament\Resources\Tickets\Pages\ListTickets;
use App\Filament\Resources\Tickets\Pages\ViewTicket;
use App\Livewire\Notifications\Index as NotificationsIndex;
use App\Livewire\Support\Index;
use App\Livewire\Support\Show;
use App\Models\AdminUser;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketClientMessageNotification;
use App\Notifications\TicketReplyNotification;
use App\Support\TicketConversation;
use App\Support\WorkspaceProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SupportTicketsTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_creates_ticket_and_admin_sees_unread_message(): void
    {
        Notification::fake();
        [$admin, $user] = $this->users();

        $this->actingAs($user);
        Livewire::test(Index::class)
            ->set('subject', 'Не приходит напоминание')
            ->set('body', 'Проверьте отправку письма.')
            ->set('priority', TicketPriority::High->value)
            ->call('create')
            ->assertHasNoErrors();

        $this->assertSame(__('app.flash.ticket_created'), session('status'));

        $ticket = Ticket::query()->firstOrFail();

        $this->assertSame($user->current_workspace_id, $ticket->workspace_id);
        $this->assertSame(TicketStatus::Open, $ticket->status);
        $this->assertSame(TicketPriority::High, $ticket->priority);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'author_type' => TicketAuthorType::Client->value,
            'read_at' => null,
        ]);
        Notification::assertSentTo($admin, TicketClientMessageNotification::class);
        $adminMail = (new TicketClientMessageNotification(
            $ticket->messages()->firstOrFail()->load('ticket.workspace', 'ticket.user'),
        ))->toMail($admin);
        $this->assertStringContainsString('/admin/tickets/'.$ticket->id, $adminMail->actionUrl);

        $this->actingAs($admin, 'admin');
        Livewire::test(ListTickets::class)
            ->assertSee('Не приходит напоминание')
            ->filterTable('status', TicketStatus::Open->value)
            ->assertSee('Не приходит напоминание');

        $this->get(AdminNotifications::getUrl())
            ->assertOk()
            ->assertSee('Не приходит напоминание');

        $this->get('/admin')
            ->assertOk()
            ->assertSee('Не приходит напоминание')
            ->assertSee('/admin/tickets/'.$ticket->id, false);
    }

    public function test_admin_reads_replies_and_client_reads_reply(): void
    {
        Notification::fake();
        [$admin, $user] = $this->users();
        $ticket = app(TicketConversation::class)->create(
            $user->currentWorkspace,
            $user,
            'Ошибка SSL',
            'Проверка завершается ошибкой.',
        );

        $this->actingAs($admin, 'admin');
        Livewire::test(ViewTicket::class, ['record' => $ticket->id])
            ->assertSet('status', TicketStatus::Open->value)
            ->set('body', 'Проверили сертификат, попробуйте снова.')
            ->call('reply')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'author_type' => TicketAuthorType::Client->value,
            'read_at' => null,
        ]);
        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'author_type' => TicketAuthorType::Admin->value,
            'read_at' => null,
        ]);
        Notification::assertSentTo($user, TicketReplyNotification::class);
        $reply = $ticket->messages()->where('author_type', TicketAuthorType::Admin->value)->firstOrFail();
        $clientMail = (new TicketReplyNotification($reply->load('ticket')))->toMail($user);
        $this->assertStringContainsString('/support/'.$ticket->id, $clientMail->actionUrl);
        $this->assertSame(TicketStatus::InProgress, $ticket->refresh()->status);

        $this->actingAs($user);
        $this->get(route('notifications'))
            ->assertOk()
            ->assertSee('Ошибка SSL')
            ->assertSee('Проверили сертификат');

        Livewire::test(NotificationsIndex::class)
            ->call('clear')
            ->assertDispatched('toast');

        $this->assertNotNull(
            $ticket->messages()->where('author_type', TicketAuthorType::Admin->value)->firstOrFail()->dismissed_at,
        );

        Livewire::test(Show::class, ['ticket' => $ticket->id])
            ->assertSee('Проверили сертификат');

        $this->assertDatabaseMissing('ticket_messages', [
            'ticket_id' => $ticket->id,
            'author_type' => TicketAuthorType::Admin->value,
            'read_at' => null,
        ]);
    }

    public function test_client_reply_reopens_closed_ticket(): void
    {
        Notification::fake();
        [, $user] = $this->users();
        $ticket = app(TicketConversation::class)->create(
            $user->currentWorkspace,
            $user,
            'Закрытый вопрос',
            'Первое сообщение.',
        );
        $ticket->update(['status' => TicketStatus::Closed]);

        $this->actingAs($user);
        Livewire::test(Show::class, ['ticket' => $ticket->id])
            ->set('body', 'Проблема появилась снова.')
            ->call('reply')
            ->assertHasNoErrors()
            ->assertDispatched('toast', message: __('app.flash.ticket_sent'), type: 'success');

        $this->assertSame(TicketStatus::Open, $ticket->refresh()->status);
    }

    public function test_client_and_admin_can_attach_private_files(): void
    {
        Notification::fake();
        Storage::fake('local');
        [$admin, $user] = $this->users();

        $this->actingAs($user);
        Livewire::test(Index::class)
            ->set('subject', 'Файлы к тикету')
            ->set('body', 'Прикладываю документ.')
            ->set('attachment', UploadedFile::fake()->create('client.pdf', 100, 'application/pdf'))
            ->call('create')
            ->assertHasNoErrors();

        $ticket = Ticket::query()->firstOrFail();
        $clientAttachment = $ticket->messages()->firstOrFail()->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($clientAttachment->path);

        $this->get(route('ticket-attachments.download', $clientAttachment))
            ->assertOk();

        $image = UploadedFile::fake()->image('screenshot.png');
        Livewire::test(Show::class, ['ticket' => $ticket->id])
            ->set('body', 'Скриншот.')
            ->set('attachment', $image)
            ->call('reply')
            ->assertHasNoErrors();

        $imageAttachment = $ticket->messages()->latest('id')->firstOrFail()->attachments()->firstOrFail();
        $this->assertTrue($imageAttachment->isImage());
        $inlineResponse = $this->get(route('ticket-attachments.download', $imageAttachment))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Cross-Origin-Resource-Policy', 'same-origin');
        $this->assertStringStartsWith(
            'inline',
            (string) $inlineResponse->headers->get('Content-Disposition'),
        );
        $inlineResponse->assertHeader('Content-Security-Policy', "sandbox; default-src 'none'");
        $this->get(route('ticket-attachments.download', ['attachment' => $imageAttachment, 'download' => 1]))
            ->assertOk()
            ->assertDownload($imageAttachment->file_name);

        $other = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Другая студия', $other);
        $this->actingAs($other)
            ->get(route('ticket-attachments.download', $clientAttachment))
            ->assertForbidden();

        $this->actingAs($admin, 'admin');
        Livewire::test(ViewTicket::class, ['record' => $ticket->id])
            ->set('body', 'Ответ с документом.')
            ->set('attachment', UploadedFile::fake()->create('admin.pdf', 120, 'application/pdf'))
            ->call('reply')
            ->assertHasNoErrors();

        $adminAttachment = $ticket->messages()->latest('id')->firstOrFail()->attachments()->firstOrFail();
        Storage::disk('local')->assertExists($adminAttachment->path);

        $this->get(route('ticket-attachments.download', $adminAttachment))
            ->assertOk();
    }

    public function test_dangerous_or_spoofed_attachment_is_rejected(): void
    {
        Notification::fake();
        [, $user] = $this->users();
        $ticket = app(TicketConversation::class)->create(
            $user->currentWorkspace,
            $user,
            'Подозрительный файл',
            'Первое сообщение.',
        );

        $this->actingAs($user);
        Livewire::test(Show::class, ['ticket' => $ticket->id])
            ->set('body', 'Файл с поддельным расширением.')
            ->set('attachment', UploadedFile::fake()->createWithContent('photo.png', '<?php phpinfo();'))
            ->call('reply')
            ->assertHasErrors('attachment');

        $this->assertSame(1, $ticket->messages()->count());
        $this->assertDatabaseCount('ticket_attachments', 0);
    }

    public function test_client_can_close_ticket(): void
    {
        Notification::fake();
        [, $user] = $this->users();
        $ticket = app(TicketConversation::class)->create(
            $user->currentWorkspace,
            $user,
            'Вопрос решён',
            'Спасибо за помощь.',
        );

        $this->actingAs($user);
        Livewire::test(Show::class, ['ticket' => $ticket->id])
            ->call('closeTicket')
            ->assertDispatched('toast', message: __('app.flash.ticket_closed'), type: 'success');

        $this->assertSame(TicketStatus::Closed, $ticket->refresh()->status);
    }

    public function test_client_and_admin_delete_ticket_with_files(): void
    {
        Notification::fake();
        Storage::fake('local');
        [$admin, $user] = $this->users();

        $this->actingAs($user);
        Livewire::test(Index::class)
            ->set('subject', 'Удаляемое обращение')
            ->set('body', 'С файлом.')
            ->set('attachment', UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf'))
            ->call('create')
            ->assertHasNoErrors();

        $ticket = Ticket::query()->firstOrFail();
        $attachment = $ticket->messages()->firstOrFail()->attachments()->firstOrFail();

        Livewire::test(Index::class)
            ->call('deleteTicket', $ticket->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseCount('ticket_attachments', 0);
        Storage::disk('local')->assertMissing($attachment->path);

        $adminTicket = app(TicketConversation::class)->create(
            $user->currentWorkspace,
            $user,
            'Тикет для админа',
            'Удалите его.',
        );

        $this->actingAs($admin, 'admin');
        Livewire::test(ListTickets::class)
            ->callTableAction('delete', $adminTicket->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('tickets', ['id' => $adminTicket->id]);
        $this->assertDatabaseCount('ticket_messages', 0);
    }

    public function test_ticket_lists_show_reply_counts_and_urgency(): void
    {
        Notification::fake();
        [$admin, $user] = $this->users();
        $ticket = app(TicketConversation::class)->create(
            $user->currentWorkspace,
            $user,
            'Срочный вопрос',
            'Нужен ответ.',
            null,
            TicketPriority::High,
        );
        app(TicketConversation::class)->replyAsAdmin($ticket, $admin, 'Разбираемся.');

        $this->actingAs($user);
        Livewire::test(Index::class)
            ->assertSee(__('app.enums.ticket_priority.high'))
            ->assertSeeHtml('mdi-fire')
            ->assertSee('1');

        $this->actingAs($admin, 'admin');
        Livewire::test(ListTickets::class)
            ->assertCanSeeTableRecords([$ticket])
            ->assertSee(__('admin.tickets.replies'));

        $counted = $user->currentWorkspace->tickets()->withActivityCounts()->firstOrFail();
        $this->assertSame(1, $counted->admin_replies_count);
        $this->assertSame(1, $counted->unread_admin_count);
        $this->assertSame(1, $counted->unread_client_count);
    }

    public function test_admin_changes_status_and_priority_separately_from_reply(): void
    {
        Notification::fake();
        [$admin, $user] = $this->users();
        $ticket = app(TicketConversation::class)->create(
            $user->currentWorkspace,
            $user,
            'Изменение статуса',
            'Возьмите в работу.',
        );

        $this->actingAs($admin, 'admin');
        Livewire::test(ViewTicket::class, ['record' => $ticket->id])
            ->set('status', TicketStatus::InProgress->value)
            ->set('priority', TicketPriority::High->value)
            ->call('updateMeta')
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertSame(TicketStatus::InProgress, $ticket->status);
        $this->assertSame(TicketPriority::High, $ticket->priority);
    }

    public function test_ticket_is_isolated_by_workspace(): void
    {
        Notification::fake();
        [, $owner] = $this->users();
        $other = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Другое агентство', $other);
        $ticket = app(TicketConversation::class)->create(
            $owner->currentWorkspace,
            $owner,
            'Чужой тикет',
            'Секретное сообщение.',
        );

        $this->actingAs($other)
            ->get(route('support.show', $ticket))
            ->assertNotFound();
    }

    /**
     * @return array{AdminUser, User}
     */
    private function users(): array
    {
        $this->seed();
        $admin = AdminUser::query()->where('email', 'admin@duvento.local')->firstOrFail();
        $user = User::factory()->create();
        app(WorkspaceProvisioner::class)->create('Студия', $user);

        return [$admin, $user->fresh()];
    }
}
