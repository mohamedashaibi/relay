<?php

namespace App\Domain\Tasks;

enum TaskStatus: string
{
    case Backlog = 'backlog';
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case InReview = 'in_review';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Backlog => 'Backlog',
            self::Todo => 'To do',
            self::InProgress => 'In progress',
            self::InReview => 'Needs review',
            self::Done => 'Done',
        };
    }

    public function isOpen(): bool
    {
        return $this !== self::Done;
    }

    /** Closing a task that is waiting on review is an approval, not a plain status change. */
    public function transitionNeedsReviewer(self $to): bool
    {
        return $this === self::InReview && $to === self::Done;
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
