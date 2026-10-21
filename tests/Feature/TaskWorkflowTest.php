<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private function project(User $owner, string $name = 'Website Relaunch'): array
    {
        return $this->actingAs($owner)->postJson('/api/projects', ['name' => $name])->assertCreated()->json('data');
    }

    private function addMember(User $owner, string $key, User $member, string $role = 'member'): void
    {
        $this->actingAs($owner)->postJson("/api/projects/{$key}/members", ['email' => $member->email, 'role' => $role])->assertCreated();
    }

    public function test_login_then_fetch_current_user(): void
    {
        User::factory()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'correct-horse-9']);

        $this->postJson('/api/auth/login', ['email' => 'Ada@Example.com', 'password' => 'correct-horse-9'])
            ->assertOk()
            ->assertJsonPath('user.email', 'ada@example.com');

        $this->getJson('/api/auth/me')->assertOk()->assertJsonPath('user.name', 'Ada');
        $this->assertArrayNotHasKey('password', $this->getJson('/api/auth/me')->json('user'));
    }

    public function test_login_rejects_bad_credentials(): void
    {
        $user = User::factory()->create();

        $this->postJson('/api/auth/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('email');
    }

    public function test_full_task_lifecycle(): void
    {
        $owner = User::factory()->create();
        $project = $this->project($owner);
        $this->assertSame('WR', $project['key']);
        $this->assertSame('owner', $project['role']);

        $task = $this->postJson('/api/tasks', [
            'project' => 'WR',
            'title' => 'Ship pricing page',
            'priority' => 'high',
            'labels' => ['Frontend', 'frontend', ' launch '],
            'assignee_id' => $owner->id,
        ])->assertCreated()->json('data');

        $this->assertSame('WR-1', $task['key']);
        $this->assertSame(['frontend', 'launch'], $task['labels']);

        $this->getJson('/api/tasks?assignee=me&include_done=0')->assertOk()->assertJsonCount(1, 'data');

        $this->postJson('/api/tasks/WR-1/status', ['status' => 'in_progress'])->assertOk()->assertJsonPath('data.status', 'in_progress');
        $this->patchJson('/api/tasks/WR-1', ['title' => 'Ship the pricing page'])->assertOk();
        $this->postJson('/api/tasks/WR-1/comments', ['body' => 'Copy is final.'])->assertCreated();
        $this->postJson('/api/tasks/WR-1/review-request', ['summary' => 'Ready — check mobile layout.'])
            ->assertOk()
            ->assertJsonPath('data.status', 'in_review')
            ->assertJsonPath('data.review_note', 'Ready — check mobile layout.');

        $detail = $this->getJson('/api/tasks/WR-1')->assertOk()->json('data');
        $types = array_column($detail['activity'], 'type');
        foreach (['created', 'status_changed', 'updated', 'commented', 'review_requested'] as $type) {
            $this->assertContains($type, $types);
        }
        $this->assertTrue($detail['permissions']['review']);

        $this->postJson('/api/tasks/WR-1/review', ['decision' => 'approve'])->assertOk()->assertJsonPath('data.status', 'done');
        $this->assertNotNull($this->getJson('/api/tasks/WR-1')->json('data.completed_at'));

        $dashboard = $this->getJson('/api/dashboard')->assertOk()->json();
        $this->assertSame(1, $dashboard['stats']['completed_this_week']);
    }

    public function test_only_the_reviewer_or_an_admin_can_close_a_task_in_review(): void
    {
        $owner = User::factory()->create();
        $dev = User::factory()->create();
        $this->project($owner);
        $this->addMember($owner, 'WR', $dev);

        $this->actingAs($owner)->postJson('/api/tasks', [
            'project' => 'WR', 'title' => 'Fix login bug', 'assignee_id' => $dev->id, 'reviewer_id' => $owner->id,
        ])->assertCreated();

        $this->actingAs($dev)->postJson('/api/tasks/WR-1/review-request', ['summary' => 'Patched the session bug'])->assertOk();

        $this->actingAs($dev)->postJson('/api/tasks/WR-1/status', ['status' => 'done'])->assertForbidden();
        $this->actingAs($dev)->postJson('/api/tasks/WR-1/review', ['decision' => 'approve'])->assertForbidden();

        $this->actingAs($owner)->postJson('/api/tasks/WR-1/review', ['decision' => 'request_changes'])
            ->assertUnprocessable()->assertJsonValidationErrors('comment');

        $this->actingAs($owner)->postJson('/api/tasks/WR-1/review', ['decision' => 'request_changes', 'comment' => 'Add a test.'])
            ->assertOk()->assertJsonPath('data.status', 'in_progress');
    }

    public function test_non_members_cannot_see_or_touch_a_project(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $this->project($owner);
        $this->actingAs($owner)->postJson('/api/tasks', ['project' => 'WR', 'title' => 'Secret'])->assertCreated();

        $this->actingAs($stranger)->getJson('/api/projects/WR')->assertNotFound();
        $this->actingAs($stranger)->getJson('/api/tasks/WR-1')->assertNotFound();
        $this->actingAs($stranger)->postJson('/api/tasks/WR-1/status', ['status' => 'done'])->assertNotFound();
        $this->actingAs($stranger)->postJson('/api/tasks', ['project' => 'WR', 'title' => 'Sneaky'])->assertNotFound();
        $this->actingAs($stranger)->getJson('/api/tasks')->assertOk()->assertJsonCount(0, 'data');
        $this->actingAs($stranger)->getJson('/api/projects')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_members_cannot_manage_the_project(): void
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $this->project($owner);
        $this->addMember($owner, 'WR', $member);

        $this->actingAs($member)->patchJson('/api/projects/WR', ['name' => 'Mine now'])->assertForbidden();
        $this->actingAs($member)->deleteJson('/api/projects/WR')->assertForbidden();
        $this->actingAs($member)->deleteJson("/api/projects/WR/members/{$owner->id}")->assertForbidden();
    }

    public function test_unexpected_fields_and_non_member_assignees_are_rejected(): void
    {
        $owner = User::factory()->create();
        $outsider = User::factory()->create();
        $this->project($owner);

        $this->actingAs($owner)->postJson('/api/tasks', ['project' => 'WR', 'title' => 'X', 'reporter_id' => 99])
            ->assertUnprocessable()->assertJsonValidationErrors('reporter_id');

        $this->actingAs($owner)->postJson('/api/tasks', ['project' => 'WR', 'title' => 'X', 'assignee_id' => $outsider->id])
            ->assertUnprocessable();
    }

    public function test_guests_are_rejected(): void
    {
        $this->getJson('/api/dashboard')->assertUnauthorized();
        $this->getJson('/api/tasks')->assertUnauthorized();
    }
}
