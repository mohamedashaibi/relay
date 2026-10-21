<?php

namespace App\Application\Projects;

use App\Application\Notifications\AccountMailer;
use App\Application\Notifications\TaskNotifications;
use App\Application\Shared\ProjectAccess;
use App\Domain\Projects\MemberView;
use App\Domain\Projects\ProjectRepository;
use App\Domain\Projects\ProjectRole;
use App\Domain\Shared\Actor;
use App\Domain\Shared\RuleViolation;
use App\Domain\Users\UserRepository;

/**
 * Adds someone by email, or changes the role of a current member.
 * Unknown emails get an invited account (assignable right away) and an invite link — Relay has no public sign-up.
 */
final class AddProjectMemberUseCase
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly UserRepository $users,
        private readonly ProjectAccess $access,
        private readonly TaskNotifications $notify,
        private readonly AccountMailer $mailer,
    ) {}

    /** @return list<MemberView> */
    public function execute(Actor $actor, string|int $projectRef, string $email, ProjectRole $role, ?string $name = null): array
    {
        $projectId = $this->access->resolveProjectId($actor, $projectRef);
        $this->access->ensureManager($actor, $projectId);

        if ($role === ProjectRole::Owner) {
            throw new RuleViolation('A project has exactly one owner.');
        }

        $email = mb_strtolower(trim($email));
        $user = $this->users->findByEmail($email)
            ?? $this->users->createInvited($this->displayName($name, $email), $email);

        $previous = $this->projects->roleOf($projectId, $user->id);
        if ($previous === ProjectRole::Owner) {
            throw new RuleViolation("The owner's role can't be changed.");
        }

        $this->projects->upsertMember($projectId, $user->id, $role);

        if ($user->pending) {
            // Adding an invited person again doubles as "resend the invite".
            $this->mailer->invite(
                $user->id,
                $this->users->find($actor->userId)?->name ?? 'A teammate',
                $this->projects->find($projectId, $actor->userId)?->name ?? 'a project',
            );
        } elseif ($previous !== $role) {
            $this->notify->addedToProject($projectId, $user->id, $role, $actor);
        }

        return $this->projects->members($projectId);
    }

    /** Until they accept, invited people show as the name given here, or their email's first part. */
    private function displayName(?string $name, string $email): string
    {
        $name = trim((string) $name);

        return $name !== '' ? $name : explode('@', $email)[0];
    }
}
