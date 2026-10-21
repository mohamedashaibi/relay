<?php

namespace App\Domain\Users;

/** Kinds of email a person can switch on or off. Everything is on by default. */
enum NotificationCategory: string
{
    case Assigned = 'assigned';
    case Status = 'status';
    case Review = 'review';
    case Comments = 'comments';
    case Projects = 'projects';

    public function label(): string
    {
        return match ($this) {
            self::Assigned => 'Tasks assigned to me',
            self::Status => 'Status changes on my tasks',
            self::Review => 'Review requests and review decisions',
            self::Comments => 'New comments on my tasks',
            self::Projects => 'Being added to a project',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Assigned => 'When someone — or their assistant — makes you the assignee.',
            self::Status => 'When a task you’re assigned to or created moves to a new status.',
            self::Review => 'When work needs your review, and when your work is approved or sent back.',
            self::Comments => 'When someone comments on a task you’re assigned to, created, or reviewing.',
            self::Projects => 'When you’re added to a project or your role changes.',
        };
    }
}
