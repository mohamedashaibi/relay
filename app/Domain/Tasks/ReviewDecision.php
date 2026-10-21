<?php

namespace App\Domain\Tasks;

enum ReviewDecision: string
{
    case Approve = 'approve';
    case RequestChanges = 'request_changes';

    public function resultingStatus(): TaskStatus
    {
        return $this === self::Approve ? TaskStatus::Done : TaskStatus::InProgress;
    }

    public function activity(): ActivityType
    {
        return $this === self::Approve ? ActivityType::Approved : ActivityType::ChangesRequested;
    }
}
