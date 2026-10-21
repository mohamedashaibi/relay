<?php

namespace App\Mcp\Tools;

use App\Application\Tasks\Dto\SearchTasksInput;
use App\Application\Tasks\SearchTasksUseCase;
use App\Domain\Tasks\TaskStatus;
use App\Domain\Tasks\TaskView;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Start here. Returns the open tasks assigned to the current user, grouped by status, plus tasks waiting for their review. Use this when the user asks "what should I work on" or "what is assigned to me".')]
class GetMyWorkTool extends RelayTool
{
    protected string $name = 'get_my_work';

    protected string $title = 'Get my work';

    public function handle(Request $request, SearchTasksUseCase $search): Response|ResponseFactory
    {
        $request->validate([
            'project' => ['nullable', 'string', 'max:20'],
            'include_backlog' => ['nullable', 'boolean'],
        ]);

        return $this->guarded(function () use ($request, $search) {
            $actor = $this->actor($request);
            $statuses = [TaskStatus::Todo, TaskStatus::InProgress, TaskStatus::InReview];
            if ($request->boolean('include_backlog')) {
                array_unshift($statuses, TaskStatus::Backlog);
            }

            $mine = $search->execute($actor, new SearchTasksInput(
                project: $request->get('project'),
                statuses: $statuses,
                assignee: 'me',
            ));
            $toReview = $search->execute($actor, new SearchTasksInput(
                project: $request->get('project'),
                awaitingMyReview: true,
            ));

            $grouped = [];
            foreach ($statuses as $status) {
                $grouped[$status->value] = $this->briefs(array_values(array_filter($mine, fn (TaskView $t) => $t->status === $status)));
            }

            return Response::structured([
                'assigned_to_me' => $grouped,
                'awaiting_my_review' => $this->briefs($toReview),
                'hint' => 'Call get_task with a key for full details. Move work forward with set_task_status, and call request_review with a summary when work is ready for a human to check.',
            ]);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Optional project key (e.g. "WEB") to narrow results.'),
            'include_backlog' => $schema->boolean()->description('Also include backlog items. Defaults to false.'),
        ];
    }
}
