<?php

namespace App\Application\Projects;

use App\Application\Shared\ProjectAccess;
use App\Domain\Attachments\AttachmentRepository;
use App\Domain\Attachments\AttachmentView;
use App\Domain\Projects\MemberView;
use App\Domain\Projects\ProjectRepository;
use App\Domain\Shared\Actor;
use App\Domain\Shared\NotFound;

final class GetProjectUseCase
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly ProjectAccess $access,
        private readonly AttachmentRepository $attachments,
    ) {}

    public function execute(Actor $actor, string|int $ref): array
    {
        $id = $this->access->resolveProjectId($actor, $ref);
        $project = $this->projects->find($id, $actor->userId) ?? throw NotFound::of('Project', $ref);

        return [
            ...$project->toArray(),
            'members' => array_map(fn (MemberView $m) => $m->toArray(), $this->projects->members($id)),
            'attachments' => array_map(fn (AttachmentView $a) => $a->toArray(), $this->attachments->forProject($id)),
        ];
    }
}
