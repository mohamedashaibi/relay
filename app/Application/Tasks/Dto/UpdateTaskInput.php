<?php

namespace App\Application\Tasks\Dto;

use App\Domain\Tasks\TaskPriority;

/**
 * Partial update. Only keys present in the payload are applied, so `assignee_id: null`
 * (unassign) is distinguishable from "assignee not mentioned".
 */
final readonly class UpdateTaskInput
{
    public const FIELDS = ['title', 'description', 'priority', 'labels', 'assignee_id', 'reviewer_id', 'due_date'];

    /** @param array<string,mixed> $changes */
    private function __construct(public array $changes) {}

    /** @param array<string,mixed> $data already-validated payload */
    public static function fromArray(array $data): self
    {
        $changes = array_intersect_key($data, array_flip(self::FIELDS));

        if (isset($changes['title'])) {
            $changes['title'] = trim($changes['title']);
        }
        if (isset($changes['priority'])) {
            $changes['priority'] = TaskPriority::from($changes['priority'])->value;
        }
        if (array_key_exists('labels', $changes)) {
            $changes['labels'] = LabelList::normalize($changes['labels'] ?? []);
        }
        foreach (['assignee_id', 'reviewer_id'] as $key) {
            if (array_key_exists($key, $changes) && $changes[$key] !== null) {
                $changes[$key] = (int) $changes[$key];
            }
        }

        return new self($changes);
    }

    public function has(string $field): bool
    {
        return array_key_exists($field, $this->changes);
    }
}
