<?php

namespace App\Application\Projects;

use App\Domain\Projects\ProjectRepository;
use App\Domain\Projects\ProjectView;
use App\Domain\Shared\Actor;

final class ListProjectsUseCase
{
    public function __construct(private readonly ProjectRepository $projects) {}

    /** @return list<ProjectView> */
    public function execute(Actor $actor, bool $includeArchived = false): array
    {
        return $this->projects->listForUser($actor->userId, $includeArchived);
    }
}
