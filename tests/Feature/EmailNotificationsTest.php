<?php

namespace Tests\Feature;

use App\Infrastructure\Mail\AccountNotification;
use App\Infrastructure\Mail\InvitationNotification;
use App\Infrastructure\Mail\ResetPasswordNotification;
use App\Infrastructure\Mail\TaskUpdateNotification;
use App\Mcp\Servers\RelayServer;
use App\Mcp\Tools\RequestReviewTool;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class EmailNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $dev;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Olivia']);
        $this->dev = User::factory()->create(['name' => 'Dev']);
        $this->actingAs($this->owner)->postJson('/api/projects', ['name' => 'Website Relaunch'])->assertCreated();
        $this->postJson('/api/projects/WR/members', ['email' => $this->dev->email, 'role' => 'member']);

        Notification::fake();
    }

    private function task(array $extra = []): void
    {
        $this->actingAs($this->owner)->postJson('/api/tasks', ['project' => 'WR', 'title' => 'Ship pricing', ...$extra])->assertCreated();
    }

    /** @return list<TaskUpdateNotification> */
    private function sentTo(User $user): array
    {
        return Notification::sent($user, TaskUpdateNotification::class)->all();
    }

    public function test_assignment_emails_the_new_assignee_but_never_the_person_acting(): void
    {
        $this->task(['assignee_id' => $this->dev->id]);

        $this->assertCount(1, $this->sentTo($this->dev));
        $this->assertSame('Olivia assigned you WR-1', $this->sentTo($this->dev)[0]->message->headline);
        Notification::assertNotSentTo($this->owner, TaskUpdateNotification::class);

        // Assigning yourself sends nothing.
        $this->task(['assignee_id' => $this->owner->id]);
        Notification::assertNotSentTo($this->owner, TaskUpdateNotification::class);
    }

    public function test_status_changes_email_the_assignee_and_creator(): void
    {
        $this->task(['assignee_id' => $this->dev->id]);
        Notification::fake();

        $this->actingAs($this->dev)->postJson('/api/tasks/WR-1/status', ['status' => 'in_progress'])->assertOk();

        $mail = $this->sentTo($this->owner);
        $this->assertCount(1, $mail);
        $this->assertSame('WR-1 moved to In progress', $mail[0]->message->subject);
        Notification::assertNotSentTo($this->dev, TaskUpdateNotification::class);
    }

    public function test_review_requests_go_to_the_reviewer_with_the_summary(): void
    {
        $this->task(['assignee_id' => $this->dev->id, 'reviewer_id' => $this->owner->id]);
        Notification::fake();

        $this->actingAs($this->dev)->postJson('/api/tasks/WR-1/review-request', ['summary' => 'Ready — check mobile layout.'])->assertOk();

        $mail = $this->sentTo($this->owner);
        $this->assertCount(1, $mail);
        $this->assertStringStartsWith('Review needed: WR-1', $mail[0]->message->subject);
        $this->assertSame('Ready — check mobile layout.', $mail[0]->message->quote);
    }

    public function test_review_requests_without_a_reviewer_go_to_project_admins(): void
    {
        $admin = User::factory()->create();
        $this->actingAs($this->owner)->postJson('/api/projects/WR/members', ['email' => $admin->email, 'role' => 'admin']);
        $this->task(['assignee_id' => $this->dev->id]);
        Notification::fake();

        $this->actingAs($this->dev)->postJson('/api/tasks/WR-1/status', ['status' => 'in_review'])->assertOk();

        $this->assertCount(1, $this->sentTo($this->owner));
        $this->assertCount(1, $this->sentTo($admin));
        Notification::assertNotSentTo($this->dev, TaskUpdateNotification::class);
    }

    public function test_review_decisions_and_comments_reach_the_people_involved(): void
    {
        $this->task(['assignee_id' => $this->dev->id, 'reviewer_id' => $this->owner->id]);
        $this->actingAs($this->dev)->postJson('/api/tasks/WR-1/review-request', ['summary' => 'Done']);
        Notification::fake();

        $this->actingAs($this->owner)->postJson('/api/tasks/WR-1/review', ['decision' => 'request_changes', 'comment' => 'Add tests.'])->assertOk();
        $this->assertSame('Changes requested on WR-1', $this->sentTo($this->dev)[0]->message->subject);
        $this->assertSame('Add tests.', $this->sentTo($this->dev)[0]->message->quote);

        Notification::fake();
        $this->actingAs($this->dev)->postJson('/api/tasks/WR-1/comments', ['body' => 'Tests added.'])->assertCreated();
        $this->assertSame('Tests added.', $this->sentTo($this->owner)[0]->message->quote);
        Notification::assertNotSentTo($this->dev, TaskUpdateNotification::class);
    }

    public function test_emails_say_when_an_ai_assistant_did_it(): void
    {
        $this->task(['assignee_id' => $this->dev->id, 'reviewer_id' => $this->owner->id]);
        Notification::fake();

        RelayServer::actingAs($this->dev)->tool(RequestReviewTool::class, ['task' => 'WR-1', 'summary' => 'Implemented and tested the layout.'])->assertOk();

        $this->assertSame('Dev (via AI agent) asked for your review on WR-1', $this->sentTo($this->owner)[0]->message->headline);
    }

    public function test_people_can_switch_email_types_off(): void
    {
        $this->actingAs($this->dev)->putJson('/api/account/email-preferences', ['preferences' => ['assigned' => false]])
            ->assertOk()
            ->assertJsonPath('email_preferences.0.key', 'assigned')
            ->assertJsonPath('email_preferences.0.enabled', false);

        $this->actingAs($this->dev)->putJson('/api/account/email-preferences', ['preferences' => ['marketing' => true]])
            ->assertUnprocessable();

        $this->task(['assignee_id' => $this->dev->id]);
        Notification::assertNotSentTo($this->dev, TaskUpdateNotification::class);
    }

    public function test_being_added_to_a_project_sends_an_email(): void
    {
        $newcomer = User::factory()->create();
        $this->actingAs($this->owner)->postJson('/api/projects/WR/members', ['email' => $newcomer->email, 'role' => 'member'])->assertCreated();

        $this->assertStringContainsString('Website Relaunch', $this->sentTo($newcomer)[0]->message->subject);
    }

    public function test_forgot_password_sends_a_reset_link_without_revealing_accounts(): void
    {
        $this->app['auth']->forgetGuards();

        $known = $this->postJson('/api/auth/forgot-password', ['email' => $this->dev->email])->assertOk()->json('message');
        $unknown = $this->postJson('/api/auth/forgot-password', ['email' => 'nobody@example.com'])->assertOk()->json('message');

        $this->assertSame($known, $unknown);
        Notification::assertSentTo($this->dev, ResetPasswordNotification::class, function ($n) {
            $mail = $n->toMail($this->dev);

            return str_contains($mail->render(), '/reset-password/') && $mail->subject === 'Reset your Relay password';
        });
    }

    public function test_resetting_the_password_works_once_and_sends_a_security_notice(): void
    {
        $this->app['auth']->forgetGuards();
        $token = Password::createToken($this->dev);

        $this->postJson('/api/auth/reset-password', ['token' => 'wrong', 'email' => $this->dev->email, 'password' => 'brand-new-pass-9'])
            ->assertUnprocessable();

        $this->postJson('/api/auth/reset-password', ['token' => $token, 'email' => $this->dev->email, 'password' => 'brand-new-pass-9'])
            ->assertOk();
        $this->postJson('/api/auth/reset-password', ['token' => $token, 'email' => $this->dev->email, 'password' => 'another-pass-99'])
            ->assertUnprocessable();

        $this->postJson('/api/auth/login', ['email' => $this->dev->email, 'password' => 'brand-new-pass-9'])->assertOk();
        Notification::assertSentTo($this->dev, AccountNotification::class, fn ($n) => $n->template === 'mail.password-changed');
    }

    public function test_changing_the_password_needs_the_current_one(): void
    {
        $this->actingAs($this->dev)->putJson('/api/account/password', ['current_password' => 'nope', 'password' => 'brand-new-pass-9'])
            ->assertUnprocessable();
        $this->actingAs($this->dev)->putJson('/api/account/password', ['current_password' => 'password', 'password' => 'brand-new-pass-9'])
            ->assertOk();

        Notification::assertSentTo($this->dev, AccountNotification::class, fn ($n) => $n->template === 'mail.password-changed');
    }

    private function inviteNadia(): string
    {
        $members = $this->actingAs($this->owner)
            ->postJson('/api/projects/WR/members', ['email' => 'Nadia@Example.com', 'role' => 'member', 'name' => 'Nadia'])
            ->assertCreated()
            ->json('data');

        $nadia = collect($members)->firstWhere('email', 'nadia@example.com');
        $this->assertTrue($nadia['pending']);
        $this->assertSame('Nadia', $nadia['name']);

        $token = null;
        Notification::assertSentTo(User::where('email', 'nadia@example.com')->first(), InvitationNotification::class, function ($n) use (&$token) {
            $token = $n->token;

            return $n->projectName === 'Website Relaunch' && $n->inviterName === 'Olivia';
        });

        return $token;
    }

    public function test_public_sign_up_is_closed(): void
    {
        $this->app['auth']->forgetGuards();
        $this->postJson('/api/auth/register', ['name' => 'Nadia', 'email' => 'nadia@example.com', 'password' => 'correct-horse-9'])->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => 'nadia@example.com']);
    }

    public function test_inviting_an_unknown_email_makes_an_assignable_member_who_cannot_sign_in_yet(): void
    {
        $this->inviteNadia();
        $nadia = User::where('email', 'nadia@example.com')->first();

        $this->task(['assignee_id' => $nadia->id]);

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/auth/login', ['email' => 'nadia@example.com', 'password' => 'anything-at-all-1'])->assertUnprocessable();
    }

    public function test_accepting_an_invite_sets_name_and_password_and_signs_in(): void
    {
        $token = $this->inviteNadia();
        $this->app['auth']->forgetGuards();

        $this->postJson('/api/auth/accept-invite', ['token' => $token, 'email' => 'nadia@example.com', 'name' => 'Nadia Salem', 'password' => 'correct-horse-9'])
            ->assertCreated()
            ->assertJsonPath('user.name', 'Nadia Salem');

        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('user.email', 'nadia@example.com');
        $nadia = User::where('email', 'nadia@example.com')->first();
        $this->assertFalse($nadia->invite_pending);
        Notification::assertSentTo($nadia, AccountNotification::class, fn ($n) => $n->template === 'mail.welcome');

        // One use only.
        $this->postJson('/api/auth/logout')->assertOk();
        $this->postJson('/api/auth/accept-invite', ['token' => $token, 'email' => 'nadia@example.com', 'name' => 'Mallory', 'password' => 'other-horse-99'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_re_adding_an_invited_person_resends_the_invite_and_kills_the_old_link(): void
    {
        $first = $this->inviteNadia();
        $this->actingAs($this->owner)->postJson('/api/projects/WR/members', ['email' => 'nadia@example.com', 'role' => 'member'])->assertCreated();
        Notification::assertSentToTimes(User::where('email', 'nadia@example.com')->first(), InvitationNotification::class, 2);

        $this->app['auth']->forgetGuards();
        $this->postJson('/api/auth/accept-invite', ['token' => $first, 'email' => 'nadia@example.com', 'name' => 'Nadia', 'password' => 'correct-horse-9'])
            ->assertUnprocessable();
    }

    public function test_members_cannot_invite(): void
    {
        $this->actingAs($this->dev)->postJson('/api/projects/WR/members', ['email' => 'nadia@example.com', 'role' => 'member'])->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'nadia@example.com']);
    }

    public function test_every_email_template_renders(): void
    {
        $this->task(['assignee_id' => $this->dev->id]);
        $notification = $this->sentTo($this->dev)[0];

        $html = $notification->toMail($this->dev)->render();
        $this->assertStringContainsString('Olivia assigned you WR-1', $html);
        $this->assertStringContainsString('/tasks/WR-1', $html);
        $this->assertStringContainsString('/account', $html);

        foreach ([AccountNotification::welcome(), AccountNotification::passwordChanged()] as $account) {
            $this->assertStringContainsString('Relay', (string) $account->toMail($this->dev)->render());
        }

        $invite = (string) (new InvitationNotification('tok', 'Olivia', 'Website Relaunch'))->toMail($this->dev)->render();
        $this->assertStringContainsString('/invite/tok?email=', $invite);
        $this->assertStringContainsString('expires in 7 days', $invite);
    }
}
