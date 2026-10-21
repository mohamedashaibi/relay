<?php

namespace App\Application\Shared;

use App\Domain\Projects\ProjectRepository;
use App\Domain\Projects\ProjectRole;
use App\Domain\Shared\Actor;
use App\Domain\Shared\Forbidden;
use App\Domain\Shared\NotFound;
use App\Domain\Shared\RuleViolation;

/** Object-level access checks shared by every use case. Non-members get NotFound, never a hint the project exists. */
final class ProjectAccess
{
    public function __construct(private readonly ProjectRepository $projects) {}

    public function role(Actor $actor, int $projectId): ProjectRole
    {
        return $this->projects->roleOf($projectId, $actor->userId)
            ?? throw NotFound::of('Project', $projectId);
    }

    public function ensureManager(Actor $actor, int $projectId): ProjectRole
    {
        $role = $this->role($actor, $projectId);

        if (! $role->canManage()) {
            throw new Forbidden('Only project owners and admins can do that.');
        }

        return $role;
    }

    public function ensureUserIsMember(int $projectId, ?int $userId, string $field): void
    {
        if ($userId !== null && $this->projects->roleOf($projectId, $userId) === null) {
            throw new RuleViolation("The {$field} must be a member of this project.");
        }
    }

    /** Accepts a numeric id or a project key such as "REL". */
    public function resolveProjectId(Actor $actor, string|int $ref): int
    {
        $id = is_int($ref) || ctype_digit($ref)
            ? (int) $ref
            : $this->projects->idForKey(strtoupper($ref));

        if ($id === null) {
            throw NotFound::of('Project', $ref);
        }

        $this->role($actor, $id);

        return $id;
    }
}
