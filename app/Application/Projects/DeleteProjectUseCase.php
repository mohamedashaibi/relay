<?php

namespace App\Application\Projects;

use App\Application\Attachments\PurgeAttachmentFiles;
use App\Application\Shared\ProjectAccess;
use App\Domain\Projects\ProjectRepository;
use App\Domain\Projects\ProjectRole;
use App\Domain\Shared\Actor;
use App\Domain\Shared\Forbidden;

final class DeleteProjectUseCase
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly ProjectAccess $access,
        private readonly PurgeAttachmentFiles $purge,
    ) {}

    public function execute(Actor $actor, string|int $projectRef): void
    {
        $projectId = $this->access->resolveProjectId($actor, $projectRef);

        if ($this->access->role($actor, $projectId) !== ProjectRole::Owner) {
            throw new Forbidden('Only the project owner can delete a project.');
        }

        $this->purge->forProject($projectId);
        $this->projects->delete($projectId);
    }
}
