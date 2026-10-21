<?php

namespace App\Application\Tasks;

use App\Application\Notifications\TaskNotifications;
use App\Application\Shared\ProjectAccess;
use App\Application\Tasks\Dto\CreateTaskInput;
use App\Domain\Shared\Actor;
use App\Domain\Tasks\ActivityType;
use App\Domain\Tasks\TaskDraft;
use App\Domain\Tasks\TaskRepository;
use App\Domain\Tasks\TaskView;

final class CreateTaskUseCase
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly ProjectAccess $access,
        private readonly TaskNotifications $notify,
    ) {}

    public function execute(Actor $actor, CreateTaskInput $input): TaskView
    {
        $projectId = $this->access->resolveProjectId($actor, $input->project);
        $this->access->ensureUserIsMember($projectId, $input->assigneeId, 'assignee');
        $this->access->ensureUserIsMember($projectId, $input->reviewerId, 'reviewer');

        $task = $this->tasks->create(new TaskDraft(
            projectId: $projectId,
            reporterId: $actor->userId,
            title: $input->title,
            description: $input->description,
            status: $input->status,
            priority: $input->priority,
            labels: $input->labels,
            assigneeId: $input->assigneeId,
            reviewerId: $input->reviewerId,
            dueDate: $input->dueDate,
        ));

        $this->tasks->log($task->id, $actor, ActivityType::Created, ['title' => $task->title]);

        if ($task->assignee !== null) {
            $this->notify->assigned($task, $actor);
        }

        return $task;
    }
}
