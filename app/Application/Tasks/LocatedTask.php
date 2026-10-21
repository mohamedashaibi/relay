<?php

namespace App\Application\Tasks;

use App\Domain\Projects\ProjectRole;
use App\Domain\Tasks\TaskView;

final readonly class LocatedTask
{
    public function __construct(
        public TaskView $task,
        public ProjectRole $role,
    ) {}
}
