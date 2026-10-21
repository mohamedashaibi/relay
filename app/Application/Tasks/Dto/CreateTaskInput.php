<?php

namespace App\Application\Tasks\Dto;

use App\Domain\Tasks\TaskPriority;
use App\Domain\Tasks\TaskStatus;

final readonly class CreateTaskInput
{
    /** @param list<string> $labels */
    public function __construct(
        public string|int $project,
        public string $title,
        public ?string $description = null,
        public TaskStatus $status = TaskStatus::Todo,
        public TaskPriority $priority = TaskPriority::Medium,
        public array $labels = [],
        public ?int $assigneeId = null,
        public ?int $reviewerId = null,
        public ?string $dueDate = null,
    ) {}

    /** @param array<string,mixed> $data already-validated payload */
    public static function fromArray(array $data): self
    {
        return new self(
            project: $data['project'],
            title: trim($data['title']),
            description: $data['description'] ?? null,
            status: TaskStatus::from($data['status'] ?? TaskStatus::Todo->value),
            priority: TaskPriority::from($data['priority'] ?? TaskPriority::Medium->value),
            labels: LabelList::normalize($data['labels'] ?? []),
            assigneeId: isset($data['assignee_id']) ? (int) $data['assignee_id'] : null,
            reviewerId: isset($data['reviewer_id']) ? (int) $data['reviewer_id'] : null,
            dueDate: $data['due_date'] ?? null,
        );
    }
}
