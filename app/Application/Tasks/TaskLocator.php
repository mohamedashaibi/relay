<?php

namespace App\Application\Tasks;

use App\Application\Shared\ProjectAccess;
use App\Domain\Shared\Actor;
use App\Domain\Shared\NotFound;
use App\Domain\Tasks\TaskRepository;

/** Resolves a task reference ("REL-12" or a numeric id) and verifies the actor belongs to its project. */
final class TaskLocator
{
    public function __construct(
        private readonly TaskRepository $tasks,
        private readonly ProjectAccess $access,
    ) {}

    public function locate(Actor $actor, string|int $ref): LocatedTask
    {
        $ref = trim((string) $ref);

        $task = match (true) {
            (bool) preg_match('/^([A-Za-z][A-Za-z0-9]{1,5})-(\d+)$/', $ref, $m) => $this->tasks->findByKey(strtoupper($m[1]), (int) $m[2]),
            ctype_digit($ref) => $this->tasks->find((int) $ref),
            default => null,
        };

        if ($task === null) {
            throw NotFound::of('Task', $ref);
        }

        // Membership failure surfaces as "task not found" so ids can't be probed.
        try {
            $role = $this->access->role($actor, $task->projectId());
        } catch (NotFound) {
            throw NotFound::of('Task', $ref);
        }

        return new LocatedTask($task, $role);
    }
}
