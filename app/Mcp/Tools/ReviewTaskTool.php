<?php

namespace App\Mcp\Tools;

use App\Application\Tasks\ResolveReviewUseCase;
use App\Domain\Tasks\ReviewDecision;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Resolves a task that is in review: approve (moves it to done) or request_changes (back to in_progress, with feedback). Only the assigned reviewer or a project admin may do this — confirm with the user before approving.')]
class ReviewTaskTool extends RelayTool
{
    protected string $name = 'review_task';

    protected string $title = 'Review task';

    public function handle(Request $request, ResolveReviewUseCase $resolve): Response|ResponseFactory
    {
        $data = $request->validate([
            'task' => ['required', 'string', 'max:30'],
            'decision' => ['required', Rule::enum(ReviewDecision::class)],
            'comment' => ['nullable', 'string', 'max:10000', 'required_if:decision,request_changes'],
        ]);

        return $this->guarded(function () use ($request, $resolve, $data) {
            $task = $resolve->execute(
                $this->actor($request),
                $data['task'],
                ReviewDecision::from($data['decision']),
                $data['comment'] ?? null,
            );

            return Response::structured(['updated' => $this->brief($task)]);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task' => $schema->string()->description('Task key such as "WEB-12".')->required(),
            'decision' => $schema->string()->enum(['approve', 'request_changes'])->required(),
            'comment' => $schema->string()->description('Feedback. Required when requesting changes.'),
        ];
    }
}
