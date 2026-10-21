<?php

namespace App\Mcp\Tools;

use App\Application\Tasks\Dto\UpdateTaskInput;
use App\Application\Tasks\UpdateTaskUseCase;
use App\Domain\Tasks\TaskPriority;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Edits a task\'s fields (title, description, priority, labels, assignee, reviewer, due date). Only the fields you pass change. To change status use set_task_status.')]
class UpdateTaskTool extends RelayTool
{
    protected string $name = 'update_task';

    protected string $title = 'Update task';

    public function handle(Request $request, UpdateTaskUseCase $update): Response|ResponseFactory
    {
        $data = $request->validate([
            'task' => ['required', 'string', 'max:30'],
            'title' => ['sometimes', 'string', 'max:200'],
            'description' => ['sometimes', 'nullable', 'string', 'max:20000'],
            'priority' => ['sometimes', Rule::enum(TaskPriority::class)],
            'labels' => ['sometimes', 'array', 'max:8'],
            'labels.*' => ['string', 'max:40'],
            'assignee' => ['sometimes', 'nullable', 'string', 'max:20'],
            'reviewer_id' => ['sometimes', 'nullable', 'integer'],
            'due_date' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ]);

        return $this->guarded(function () use ($request, $update, $data) {
            $actor = $this->actor($request);

            if (array_key_exists('assignee', $data)) {
                $a = $data['assignee'];
                $data['assignee_id'] = match (true) {
                    $a === 'me' => $actor->userId,
                    ctype_digit((string) $a) => (int) $a,
                    default => null,
                };
            }

            $task = $update->execute($actor, $data['task'], UpdateTaskInput::fromArray($data));

            return Response::structured(['updated' => $this->brief($task)]);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task' => $schema->string()->description('Task key such as "WEB-12".')->required(),
            'title' => $schema->string(),
            'description' => $schema->string(),
            'priority' => $schema->string()->enum(TaskPriority::values()),
            'labels' => $schema->array()->items($schema->string())->description('Replaces the full label list.'),
            'assignee' => $schema->string()->description('"me", a user id, or "none" to unassign.'),
            'reviewer_id' => $schema->integer(),
            'due_date' => $schema->string()->description('YYYY-MM-DD'),
        ];
    }
}
