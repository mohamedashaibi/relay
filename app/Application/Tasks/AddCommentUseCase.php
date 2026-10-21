<?php

namespace App\Application\Tasks;

use App\Application\Notifications\TaskNotifications;
use App\Domain\Shared\Actor;
use App\Domain\Tasks\ActivityType;
use App\Domain\Tasks\CommentView;
use App\Domain\Tasks\TaskRepository;

final class AddCommentUseCase
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly TaskLocator $locator,
        private readonly TaskNotifications $notify,
    ) {}

    public function execute(Actor $actor, string|int $ref, string $body): CommentView
    {
        $task = $this->locator->locate($actor, $ref)->task;

        $comment = $this->tasks->addComment($task->id, $actor, trim($body));
        $this->tasks->log($task->id, $actor, ActivityType::Commented, ['excerpt' => mb_strimwidth(trim($body), 0, 140, '…')]);
        $this->notify->commented($task, $comment, $actor);

        return $comment;
    }
}
