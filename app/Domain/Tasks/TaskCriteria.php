<?php

namespace App\Domain\Tasks;

final readonly class TaskCriteria
{
    /**
     * @param  list<int>  $projectIds  always constrained to projects the viewer belongs to
     * @param  list<TaskStatus>  $statuses
     */
    public function __construct(
        public array $projectIds,
        public array $statuses = [],
        public ?int $assigneeId = null,
        public bool $unassigned = false,
        public ?int $reviewerId = null,
        public ?TaskPriority $priority = null,
        public ?string $text = null,
        public ?string $label = null,
        public bool $overdueOnly = false,
        public int $limit = 200,
    ) {}
}
