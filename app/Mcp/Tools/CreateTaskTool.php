<?php

namespace App\Mcp\Tools;

use App\Application\Tasks\CreateTaskUseCase;
use App\Application\Tasks\Dto\CreateTaskInput;
use App\Domain\Tasks\TaskPriority;
use App\Domain\Tasks\TaskStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Creates a task in a project. Use assignee "me" to assign it to the current user.')]
class CreateTaskTool extends RelayTool
{
    protected string $name = 'create_task';

    protected string $title = 'Create task';

    public function handle(Request $request, CreateTaskUseCase $create): Response|ResponseFactory
    {
        $data = $request->validate([
            'project' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:20000'],
            'status' => ['nullable', Rule::enum(TaskStatus::class)],
            'priority' => ['nullable', Rule::enum(TaskPriority::class)],
            'labels' => ['nullable', 'array', 'max:8'],
            'labels.*' => ['string', 'max:40'],
            'assignee' => ['nullable', 'string', 'max:20'],
            'reviewer_id' => ['nullable', 'integer'],
            'due_date' => ['nullable', 'date_format:Y-m-d'],
        ]);

        return $this->guarded(function () use ($request, $create, $data) {
            $actor = $this->actor($request);
            $assignee = $data['assignee'] ?? null;
            $data['assignee_id'] = $assignee === 'me' ? $actor->userId : (ctype_digit((string) $assignee) ? (int) $assignee : null);

            $task = $create->execute($actor, CreateTaskInput::fromArray($data));

            return Response::structured(['created' => $this->brief($task)]);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project key, e.g. "WEB".')->required(),
            'title' => $schema->string()->description('Short, imperative title.')->required(),
            'description' => $schema->string()->description('Markdown description with context and acceptance criteria.'),
            'status' => $schema->string()->enum(TaskStatus::values())->description('Defaults to todo.'),
            'priority' => $schema->string()->enum(TaskPriority::values())->description('Defaults to medium.'),
            'labels' => $schema->array()->items($schema->string()),
            'assignee' => $schema->string()->description('"me" or a member\'s user id (see get_project).'),
            'reviewer_id' => $schema->integer()->description('User id of the person who should review the work.'),
            'due_date' => $schema->string()->description('YYYY-MM-DD'),
        ];
    }
}
