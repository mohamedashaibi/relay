<?php

namespace App\Application\Tasks;

use App\Application\Notifications\TaskNotifications;
use App\Application\Shared\ProjectAccess;
use App\Application\Tasks\Dto\UpdateTaskInput;
use App\Domain\Shared\Actor;
use App\Domain\Tasks\ActivityType;
use App\Domain\Tasks\TaskRepository;
use App\Domain\Tasks\TaskView;

final class UpdateTaskUseCase
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly TaskLocator $locator,
        private readonly ProjectAccess $access,
        private readonly TaskNotifications $notify,
    ) {}

    public function execute(Actor $actor, string|int $ref, UpdateTaskInput $input): TaskView
    {
        $before = $this->locator->locate($actor, $ref)->task;

        if ($input->has('assignee_id')) {
            $this->access->ensureUserIsMember($before->projectId(), $input->changes['assignee_id'], 'assignee');
        }
        if ($input->has('reviewer_id')) {
            $this->access->ensureUserIsMember($before->projectId(), $input->changes['reviewer_id'], 'reviewer');
        }

        $current = [
            'title' => $before->title,
            'description' => $before->description,
            'priority' => $before->priority->value,
            'labels' => $before->labels,
            'assignee_id' => $before->assignee['id'] ?? null,
            'reviewer_id' => $before->reviewer['id'] ?? null,
            'due_date' => $before->dueDate,
        ];
        $changes = array_filter(
            $input->changes,
            fn ($value, $field) => $current[$field] !== $value,
            ARRAY_FILTER_USE_BOTH,
        );

        if ($changes === []) {
            return $before;
        }

        $after = $this->tasks->update($before->id, $changes);

        if (array_key_exists('assignee_id', $changes)) {
            $this->tasks->log($after->id, $actor, ActivityType::Assigned, [
                'from' => $before->assignee['name'] ?? null,
                'to' => $after->assignee['name'] ?? null,
            ]);
            if ($after->assignee !== null) {
                $this->notify->assigned($after, $actor);
            }
            unset($changes['assignee_id']);
        }

        if ($changes !== []) {
            $this->tasks->log($after->id, $actor, ActivityType::Updated, ['fields' => array_keys($changes)]);
        }

        return $after;
    }
}
