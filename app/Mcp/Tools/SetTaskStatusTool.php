<?php

namespace App\Mcp\Tools;

use App\Application\Tasks\ChangeTaskStatusUseCase;
use App\Domain\Tasks\TaskStatus;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;

#[IsIdempotent]
#[Description('Moves a task to a new status: backlog, todo, in_progress, in_review or done. Prefer request_review over setting in_review directly, so the reviewer gets a summary.')]
class SetTaskStatusTool extends RelayTool
{
    protected string $name = 'set_task_status';

    protected string $title = 'Set task status';

    public function handle(Request $request, ChangeTaskStatusUseCase $change): Response|ResponseFactory
    {
        $data = $request->validate([
            'task' => ['required', 'string', 'max:30'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ]);

        return $this->guarded(function () use ($request, $change, $data) {
            $task = $change->execute($this->actor($request), $data['task'], TaskStatus::from($data['status']));

            return Response::structured(['updated' => $this->brief($task)]);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task' => $schema->string()->description('Task key such as "WEB-12".')->required(),
            'status' => $schema->string()->enum(TaskStatus::values())->required(),
        ];
    }
}
