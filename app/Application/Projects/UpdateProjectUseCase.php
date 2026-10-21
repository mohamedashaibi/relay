<?php

namespace App\Application\Projects;

use App\Application\Projects\Dto\UpdateProjectInput;
use App\Application\Shared\ProjectAccess;
use App\Domain\Projects\ProjectRepository;
use App\Domain\Projects\ProjectView;
use App\Domain\Shared\Actor;

final class UpdateProjectUseCase
{
    public function __construct(
        private readonly ProjectRepository $projects,
        private readonly ProjectAccess $access,
    ) {}

    public function execute(Actor $actor, string|int $projectRef, UpdateProjectInput $input): ProjectView
    {
        $projectId = $this->access->resolveProjectId($actor, $projectRef);
        $this->access->ensureManager($actor, $projectId);

        if ($input->changes !== []) {
            $this->projects->update($projectId, $input->changes);
        }

        return $this->projects->find($projectId, $actor->userId);
    }
}
