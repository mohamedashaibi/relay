<?php

namespace App\Application\Tasks;

use App\Application\Shared\ProjectAccess;
use App\Application\Tasks\Dto\SearchTasksInput;
use App\Domain\Projects\ProjectRepository;
use App\Domain\Shared\Actor;
use App\Domain\Tasks\TaskCriteria;
use App\Domain\Tasks\TaskRepository;
use App\Domain\Tasks\TaskStatus;
use App\Domain\Tasks\TaskView;

final class SearchTasksUseCase
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly ProjectRepository $projects,
        private readonly ProjectAccess $access,
    ) {}

    /** @return list<TaskView> */
    public function execute(Actor $actor, SearchTasksInput $input): array
    {
        $projectIds = $input->project !== null
            ? [$this->access->resolveProjectId($actor, $input->project)]
            : $this->projects->projectIdsForUser($actor->userId);

        if ($projectIds === []) {
            return [];
        }

        $statuses = match (true) {
            $input->awaitingMyReview => [TaskStatus::InReview],
            $input->statuses !== [] => $input->statuses,
            ! $input->includeDone => array_values(array_filter(TaskStatus::cases(), fn (TaskStatus $s) => $s->isOpen())),
            default => [],
        };

        return $this->tasks->search(new TaskCriteria(
            projectIds: $projectIds,
            statuses: $statuses,
            assigneeId: match (true) {
                $input->assignee === 'me' => $actor->userId,
                is_int($input->assignee) => $input->assignee,
                default => null,
            },
            unassigned: $input->assignee === 'unassigned',
            reviewerId: $input->awaitingMyReview ? $actor->userId : null,
            priority: $input->priority,
            text: $input->text,
            label: $input->label,
            overdueOnly: $input->overdueOnly,
            limit: max(1, min($input->limit, 500)),
        ));
    }
}
