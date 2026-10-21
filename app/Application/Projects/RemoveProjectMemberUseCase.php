<?php

namespace App\Application\Projects;

use App\Application\Shared\ProjectAccess;
use App\Domain\Projects\ProjectRepository;
use App\Domain\Projects\ProjectRole;
use App\Domain\Shared\Actor;
use App\Domain\Shared\RuleViolation;

final class RemoveProjectMemberUseCase
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly ProjectAccess $access,
    ) {}

    public function execute(Actor $actor, string|int $projectRef, int $userId): void
    {
        $projectId = $this->access->resolveProjectId($actor, $projectRef);

        // Members may leave on their own; removing someone else needs a manager.
        if ($userId !== $actor->userId) {
            $this->access->ensureManager($actor, $projectId);
        }

        $role = $this->projects->roleOf($projectId, $userId);

        if ($role === ProjectRole::Owner) {
            throw new RuleViolation('The owner cannot be removed. Delete the project instead.');
        }

        if ($role !== null) {
            $this->projects->removeMember($projectId, $userId);
        }
    }
}
