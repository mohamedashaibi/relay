<?php

namespace App\Mcp\Tools;

use App\Application\Tasks\AddCommentUseCase;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Adds a comment to a task — progress notes, questions, or findings. Markdown is supported.')]
class AddCommentTool extends RelayTool
{
    protected string $name = 'add_comment';

    protected string $title = 'Add comment';

    public function handle(Request $request, AddCommentUseCase $comment): Response|ResponseFactory
    {
        $data = $request->validate([
            'task' => ['required', 'string', 'max:30'],
            'body' => ['required', 'string', 'max:10000'],
        ]);

        return $this->guarded(function () use ($request, $comment, $data) {
            $c = $comment->execute($this->actor($request), $data['task'], $data['body']);

            return Response::structured(['comment_id' => $c->id, 'task' => $data['task'], 'posted_at' => $c->createdAt]);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task' => $schema->string()->description('Task key such as "WEB-12".')->required(),
            'body' => $schema->string()->required(),
        ];
    }
}
