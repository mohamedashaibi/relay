<?php

namespace App\Domain\Tasks;

enum ActivityType: string
{
    case Created = 'created';
    case Updated = 'updated';
    case StatusChanged = 'status_changed';
    case Assigned = 'assigned';
    case ReviewRequested = 'review_requested';
    case Approved = 'approved';
    case ChangesRequested = 'changes_requested';
    case Commented = 'commented';
    case Attached = 'attached';
}
