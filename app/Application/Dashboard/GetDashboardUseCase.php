<?php

namespace App\Application\Dashboard;

use App\Domain\Projects\ProjectRepository;
use App\Domain\Shared\Actor;
use App\Domain\Tasks\ActivityView;
use App\Domain\Tasks\TaskCriteria;
use App\Domain\Tasks\TaskRepository;
use App\Domain\Tasks\TaskStatus;
use App\Domain\Tasks\TaskView;

final class GetDashboardUseCase
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly ProjectRepository $projects,
    ) {}

    public function execute(Actor $actor): array
    {
        $projectIds = $this->projects->projectIdsForUser($actor->userId);
        $open = array_values(array_filter(TaskStatus::cases(), fn (TaskStatus $s) => $s->isOpen() && $s !== TaskStatus::Backlog));

        $myTasks = $projectIds === [] ? [] : $this->tasks->search(new TaskCriteria(
            projectIds: $projectIds,
            statuses: $open,
            assigneeId: $actor->userId,
            limit: 12,
        ));

        $reviewQueue = $projectIds === [] ? [] : $this->tasks->search(new TaskCriteria(
            projectIds: $projectIds,
            statuses: [TaskStatus::InReview],
            reviewerId: $actor->userId,
            limit: 6,
        ));

        return [
            'stats' => $this->tasks->statsFor($actor->userId, $projectIds),
            'my_tasks' => array_map(fn (TaskView $t) => $t->toArray(), $myTasks),
            'review_queue' => array_map(fn (TaskView $t) => $t->toArray(), $reviewQueue),
            'activity' => array_map(fn (ActivityView $a) => $a->toArray(), $this->tasks->recentActivity($projectIds, 15)),
        ];
    }
}
