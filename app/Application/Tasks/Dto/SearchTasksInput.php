<?php

namespace App\Application\Tasks\Dto;

use App\Domain\Tasks\TaskPriority;
use App\Domain\Tasks\TaskStatus;

final readonly class SearchTasksInput
{
    /**
     * @param  list<TaskStatus>  $statuses
     * @param  'me'|'unassigned'|int|null  $assignee
     */
    public function __construct(
        public string|int|null $project = null,
        public array $statuses = [],
        public string|int|null $assignee = null,
        public bool $awaitingMyReview = false,
        public ?TaskPriority $priority = null,
        public ?string $text = null,
        public ?string $label = null,
        public bool $includeDone = true,
        public bool $overdueOnly = false,
        public int $limit = 200,
    ) {}

    /** @param array<string,mixed> $data already-validated payload */
    public static function fromArray(array $data): self
    {
        $statuses = $data['status'] ?? [];
        $statuses = is_array($statuses) ? $statuses : explode(',', (string) $statuses);

        $assignee = $data['assignee'] ?? null;
        if (is_string($assignee) && ctype_digit($assignee)) {
            $assignee = (int) $assignee;
        }

        return new self(
            project: $data['project'] ?? null,
            statuses: array_map(fn ($s) => TaskStatus::from(trim($s)), array_filter($statuses)),
            assignee: $assignee,
            awaitingMyReview: (bool) ($data['awaiting_my_review'] ?? false),
            priority: isset($data['priority']) ? TaskPriority::from($data['priority']) : null,
            text: isset($data['q']) && trim($data['q']) !== '' ? trim($data['q']) : null,
            label: $data['label'] ?? null,
            includeDone: (bool) ($data['include_done'] ?? true),
            overdueOnly: (bool) ($data['overdue'] ?? false),
            limit: (int) ($data['limit'] ?? 200),
        );
    }
}
