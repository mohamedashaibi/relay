<?php

namespace Tests\Feature;

use App\Mcp\Servers\RelayServer;
use App\Mcp\Tools\AddCommentTool;
use App\Mcp\Tools\CreateTaskTool;
use App\Mcp\Tools\GetMyWorkTool;
use App\Mcp\Tools\GetTaskTool;
use App\Mcp\Tools\RequestReviewTool;
use App\Mcp\Tools\ReviewTaskTool;
use App\Mcp\Tools\SearchTasksTool;
use App\Mcp\Tools\SetTaskStatusTool;
use App\Models\TaskActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $dev;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['name' => 'Olivia']);
        $this->dev = User::factory()->create(['name' => 'Dev']);

        $this->actingAs($this->owner)->postJson('/api/projects', ['name' => 'Platform', 'key' => 'PLT'])->assertCreated();
        $this->postJson('/api/projects/PLT/members', ['email' => $this->dev->email, 'role' => 'member'])->assertCreated();
        $this->postJson('/api/tasks', [
            'project' => 'PLT', 'title' => 'Add rate limiting', 'assignee_id' => $this->dev->id, 'reviewer_id' => $this->owner->id,
        ])->assertCreated();
    }

    public function test_agent_sees_its_users_work(): void
    {
        RelayServer::actingAs($this->dev)->tool(GetMyWorkTool::class)
            ->assertOk()
            ->assertSee('PLT-1')
            ->assertSee('Add rate limiting');

        RelayServer::actingAs($this->owner)->tool(GetMyWorkTool::class)
            ->assertOk()
            ->assertDontSee('PLT-1');
    }

    public function test_agent_works_a_task_through_to_review(): void
    {
        RelayServer::actingAs($this->dev)->tool(SetTaskStatusTool::class, ['task' => 'PLT-1', 'status' => 'in_progress'])->assertOk();
        RelayServer::actingAs($this->dev)->tool(AddCommentTool::class, ['task' => 'plt-1', 'body' => 'Using a token bucket.'])->assertOk();
        RelayServer::actingAs($this->dev)->tool(RequestReviewTool::class, [
            'task' => 'PLT-1', 'summary' => 'Added throttle middleware and tests; check the limits.',
        ])->assertOk()->assertSee('in_review');

        $activity = TaskActivity::where('type', 'review_requested')->firstOrFail();
        $this->assertSame('mcp', $activity->channel);

        RelayServer::actingAs($this->owner)->tool(GetMyWorkTool::class)->assertOk()->assertSee('PLT-1');
        RelayServer::actingAs($this->dev)->tool(GetTaskTool::class, ['task' => 'PLT-1'])
            ->assertOk()->assertSee('Using a token bucket.')->assertSee('check the limits');
    }

    public function test_agent_cannot_self_approve_when_someone_else_reviews(): void
    {
        RelayServer::actingAs($this->dev)->tool(RequestReviewTool::class, ['task' => 'PLT-1', 'summary' => 'Done, please check it.'])->assertOk();

        RelayServer::actingAs($this->dev)->tool(SetTaskStatusTool::class, ['task' => 'PLT-1', 'status' => 'done'])
            ->assertHasErrors(['waiting on review by Olivia']);
        RelayServer::actingAs($this->dev)->tool(ReviewTaskTool::class, ['task' => 'PLT-1', 'decision' => 'approve'])
            ->assertHasErrors();

        RelayServer::actingAs($this->owner)->tool(ReviewTaskTool::class, ['task' => 'PLT-1', 'decision' => 'approve'])
            ->assertOk()->assertSee('done');
    }

    public function test_agent_creates_and_searches_tasks(): void
    {
        RelayServer::actingAs($this->dev)->tool(CreateTaskTool::class, [
            'project' => 'PLT', 'title' => 'Write runbook', 'assignee' => 'me', 'priority' => 'urgent', 'labels' => ['Docs'],
        ])->assertOk()->assertSee('PLT-2');

        RelayServer::actingAs($this->dev)->tool(SearchTasksTool::class, ['priority' => 'urgent'])
            ->assertOk()->assertSee('Write runbook')->assertDontSee('Add rate limiting');

        RelayServer::actingAs($this->dev)->tool(SearchTasksTool::class, ['status' => ['nonsense']])->assertHasErrors();
    }

    public function test_outsiders_get_not_found_not_data(): void
    {
        $stranger = User::factory()->create();

        RelayServer::actingAs($stranger)->tool(GetTaskTool::class, ['task' => 'PLT-1'])->assertHasErrors(['not found']);
        RelayServer::actingAs($stranger)->tool(CreateTaskTool::class, ['project' => 'PLT', 'title' => 'x'])->assertHasErrors();
    }

    public function test_http_endpoint_requires_a_token_and_accepts_a_personal_token(): void
    {
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->assertUnauthorized();

        $token = $this->actingAs($this->dev)->postJson('/api/connections', ['name' => 'Claude Code'])
            ->assertCreated()->json('data.token');
        $this->assertNotEmpty($token);

        $this->app['auth']->forgetGuards();

        $this->withHeaders(['Authorization' => "Bearer {$token}", 'Accept' => 'application/json, text/event-stream'])
            ->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'get_my_work', 'arguments' => (object) []]])
            ->assertOk()
            ->assertSee('PLT-1');

        $connections = $this->actingAs($this->dev, 'web')->getJson('/api/connections')->assertOk()->json('data');
        $this->assertSame('Claude Code', $connections[0]['name']);

        $this->deleteJson('/api/connections/'.$connections[0]['id'])->assertNoContent();
        $this->getJson('/api/connections')->assertJsonCount(0, 'data');
    }
}
