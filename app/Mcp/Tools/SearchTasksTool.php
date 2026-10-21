<?php

namespace App\Mcp\Tools;

use App\Application\Tasks\Dto\SearchTasksInput;
use App\Application\Tasks\SearchTasksUseCase;
use App\Domain\Tasks\TaskPriority;
use App\Domain\Tasks\TaskStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Searches tasks across the user\'s projects. Filter by project, status, assignee, priority, label or free text. Returns compact task summaries.')]
class SearchTasksTool extends RelayTool
{
    protected string $name = 'search_tasks';

    protected string $title = 'Search tasks';

    public function handle(Request $request, SearchTasksUseCase $search): Response|ResponseFactory
    {
        $data = $request->validate([
            'project' => ['nullable', 'string', 'max:20'],
            'status' => ['nullable', 'array'],
            'status.*' => [Rule::enum(TaskStatus::class)],
            'assignee' => ['nullable', 'string', 'max:20'],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'q' => ['nullable', 'string', 'max:200'],
            'label' => ['nullable', 'string', 'max:40'],
            'overdue' => ['nullable', 'boolean'],
            'include_done' => ['nullable', 'boolean'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $data['limit'] ??= 30;
        $data['include_done'] ??= false;

        return $this->guarded(function () use ($request, $search, $data) {
            $tasks = $search->execute($this->actor($request), SearchTasksInput::fromArray($data));

            return Response::structured(['count' => count($tasks), 'tasks' => $this->briefs($tasks)]);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project key, e.g. "WEB".'),
            'status' => $schema->array()->items($schema->string()->enum(TaskStatus::values()))
                ->description('One or more statuses. in_review means "needs review".'),
            'assignee' => $schema->string()->description('"me", "unassigned", or a user id.'),
            'priority' => $schema->string()->enum(TaskPriority::values()),
            'q' => $schema->string()->description('Text to match in title or description.'),
            'label' => $schema->string(),
            'overdue' => $schema->boolean()->description('Only open tasks past their due date.'),
            'include_done' => $schema->boolean()->description('Include completed tasks. Defaults to false.'),
            'limit' => $schema->integer()->description('Max results, 1-100. Defaults to 30.'),
        ];
    }
}
