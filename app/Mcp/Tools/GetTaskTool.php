<?php

namespace App\Mcp\Tools;

use App\Application\Tasks\GetTaskUseCase;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Returns a task\'s full details: description, review note, comments, recent activity and attached files. Read a file with read_attachment.')]
class GetTaskTool extends RelayTool
{
    protected string $name = 'get_task';

    protected string $title = 'Get task';

    public function handle(Request $request, GetTaskUseCase $getTask): Response|ResponseFactory
    {
        $data = $request->validate(['task' => ['required', 'string', 'max:30']]);

        return $this->guarded(function () use ($request, $getTask, $data) {
            $detail = $getTask->execute($this->actor($request), $data['task']);

            return Response::structured([
                ...$this->brief($detail->task),
                'description' => $detail->task->description,
                'review_note' => $detail->task->reviewNote,
                'reporter' => $detail->task->reporter['name'] ?? null,
                'created_at' => $detail->task->createdAt,
                'updated_at' => $detail->task->updatedAt,
                'comments' => array_map(fn ($c) => [
                    'author' => $c->author['name'] ?? 'Deleted user',
                    'via' => $c->client,
                    'body' => $c->body,
                    'at' => $c->createdAt,
                ], $detail->comments),
                'recent_activity' => array_map(fn ($a) => [
                    'type' => $a->type->value,
                    'by' => $a->actor['name'] ?? null,
                    'via' => $a->client,
                    'meta' => $a->meta,
                    'at' => $a->createdAt,
                ], array_slice($detail->activity, 0, 10)),
                'attachments' => array_map(fn ($a) => $this->fileBrief($a), $detail->attachments),
                'you_can_review' => $detail->canReview,
            ]);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task' => $schema->string()->description('Task key such as "WEB-12".')->required(),
        ];
    }
}
