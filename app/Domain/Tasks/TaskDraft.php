<?php

namespace App\Domain\Tasks;

/** A validated new task, ready to persist. */
final readonly class TaskDraft
{
    /** @param list<string> $labels */
    public function __construct(
        public int $projectId,
        public int $reporterId,
        public string $title,
        public ?string $description,
        public TaskStatus $status,
        public TaskPriority $priority,
        public array $labels,
        public ?int $assigneeId,
        public ?int $reviewerId,
        public ?string $dueDate,
    ) {}
}
