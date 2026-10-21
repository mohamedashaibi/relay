<?php

namespace App\Http\Controllers\Api;

use App\Application\Tasks\AddCommentUseCase;
use App\Application\Tasks\ChangeTaskStatusUseCase;
use App\Application\Tasks\CreateTaskUseCase;
use App\Application\Tasks\DeleteTaskUseCase;
use App\Application\Tasks\Dto\CreateTaskInput;
use App\Application\Tasks\Dto\SearchTasksInput;
use App\Application\Tasks\Dto\UpdateTaskInput;
use App\Application\Tasks\GetTaskUseCase;
use App\Application\Tasks\RequestReviewUseCase;
use App\Application\Tasks\ResolveReviewUseCase;
use App\Application\Tasks\SearchTasksUseCase;
use App\Application\Tasks\UpdateTaskUseCase;
use App\Domain\Tasks\ReviewDecision;
use App\Domain\Tasks\TaskStatus;
use App\Domain\Tasks\TaskView;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeStatusRequest;
use App\Http\Requests\CommentRequest;
use App\Http\Requests\RequestReviewRequest;
use App\Http\Requests\ResolveReviewRequest;
use App\Http\Requests\SearchTasksRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function index(SearchTasksRequest $request, SearchTasksUseCase $search): JsonResponse
    {
        $tasks = $search->execute($this->actor($request), SearchTasksInput::fromArray($request->validated()));

        return response()->json(['data' => array_map(fn (TaskView $t) => $t->toArray(), $tasks)]);
    }

    public function store(StoreTaskRequest $request, CreateTaskUseCase $create): JsonResponse
    {
        $task = $create->execute($this->actor($request), CreateTaskInput::fromArray($request->validated()));

        return response()->json(['data' => $task->toArray()], 201);
    }

    public function show(Request $request, string $task, GetTaskUseCase $get): JsonResponse
    {
        return response()->json(['data' => $get->execute($this->actor($request), $task)->toArray()]);
    }

    public function update(UpdateTaskRequest $request, string $task, UpdateTaskUseCase $update): JsonResponse
    {
        $updated = $update->execute($this->actor($request), $task, UpdateTaskInput::fromArray($request->validated()));

        return response()->json(['data' => $updated->toArray()]);
    }

    public function destroy(Request $request, string $task, DeleteTaskUseCase $delete): JsonResponse
    {
        $delete->execute($this->actor($request), $task);

        return response()->json(null, 204);
    }

    public function status(ChangeStatusRequest $request, string $task, ChangeTaskStatusUseCase $change): JsonResponse
    {
        $data = $request->validated();
        $updated = $change->execute(
            $this->actor($request),
            $task,
            TaskStatus::from($data['status']),
            isset($data['position']) ? (float) $data['position'] : null,
        );

        return response()->json(['data' => $updated->toArray()]);
    }

    public function requestReview(RequestReviewRequest $request, string $task, RequestReviewUseCase $requestReview): JsonResponse
    {
        $data = $request->validated();
        $updated = $requestReview->execute(
            $this->actor($request),
            $task,
            $data['summary'],
            isset($data['reviewer_id']) ? (int) $data['reviewer_id'] : null,
        );

        return response()->json(['data' => $updated->toArray()]);
    }

    public function review(ResolveReviewRequest $request, string $task, ResolveReviewUseCase $resolve): JsonResponse
    {
        $data = $request->validated();
        $updated = $resolve->execute($this->actor($request), $task, ReviewDecision::from($data['decision']), $data['comment'] ?? null);

        return response()->json(['data' => $updated->toArray()]);
    }

    public function comment(CommentRequest $request, string $task, AddCommentUseCase $comment): JsonResponse
    {
        return response()->json(['data' => $comment->execute($this->actor($request), $task, $request->validated('body'))->toArray()], 201);
    }
}
