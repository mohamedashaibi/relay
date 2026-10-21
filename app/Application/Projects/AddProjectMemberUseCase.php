<?php

namespace App\Application\Projects;

use App\Application\Notifications\TaskNotifications;
use App\Application\Shared\ProjectAccess;
use App\Domain\Projects\MemberView;
use App\Domain\Projects\ProjectRepository;
use App\Domain\Projects\ProjectRole;
use App\Domain\Shared\Actor;
use App\Domain\Shared\RuleViolation;
use App\Domain\Users\UserRepository;

/** Adds an existing user by email, or changes the role of a current member. */
final class AddProjectMemberUseCase
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly UserRepository $users,
        private readonly ProjectAccess $access,
        private readonly TaskNotifications $notify,
    ) {}

    /** @return list<MemberView> */
    public function execute(Actor $actor, string|int $projectRef, string $email, ProjectRole $role): array
    {
        $projectId = $this->access->resolveProjectId($actor, $projectRef);
        $this->access->ensureManager($actor, $projectId);

        if ($role === ProjectRole::Owner) {
            throw new RuleViolation('A project has exactly one owner.');
        }

        $user = $this->users->findByEmail($email)
            ?? throw new RuleViolation('No Relay account uses that email. Ask them to sign up first.');

        $previous = $this->projects->roleOf($projectId, $user->id);
        if ($previous === ProjectRole::Owner) {
            throw new RuleViolation("The owner's role can't be changed.");
        }

        $this->projects->upsertMember($projectId, $user->id, $role);

        if ($previous !== $role) {
            $this->notify->addedToProject($projectId, $user->id, $role, $actor);
        }

        return $this->projects->members($projectId);
    }
}
