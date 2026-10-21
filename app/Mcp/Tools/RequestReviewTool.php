<?php

namespace App\Mcp\Tools;

use App\Application\Tasks\RequestReviewUseCase;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Marks a task as needing review and hands it to a human. Always include a summary of what was done, what to check, and anything unresolved.')]
class RequestReviewTool extends RelayTool
{
    protected string $name = 'request_review';

    protected string $title = 'Request review';

    public function handle(Request $request, RequestReviewUseCase $requestReview): Response|ResponseFactory
    {
        $data = $request->validate([
            'task' => ['required', 'string', 'max:30'],
            'summary' => ['required', 'string', 'min:10', 'max:10000'],
            'reviewer_id' => ['nullable', 'integer'],
        ]);

        return $this->guarded(function () use ($request, $requestReview, $data) {
            $task = $requestReview->execute(
                $this->actor($request),
                $data['task'],
                $data['summary'],
                isset($data['reviewer_id']) ? (int) $data['reviewer_id'] : null,
            );

            return Response::structured(['in_review' => $this->brief($task)]);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task' => $schema->string()->description('Task key such as "WEB-12".')->required(),
            'summary' => $schema->string()->description('What changed, how it was verified, and what the reviewer should look at.')->required(),
            'reviewer_id' => $schema->integer()->description('Optional reviewer user id; keeps the existing reviewer if omitted.'),
        ];
    }
}
