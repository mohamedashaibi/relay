<?php

namespace App\Application\Notifications;

use App\Domain\Users\NotificationCategory;

/** A task email, described without any mail-framework types. Paths are app-relative ("/tasks/WEB-12"). */
final readonly class EmailMessage
{
    /** @param list<string> $lines */
    public function __construct(
        public NotificationCategory $category,
        public string $subject,
        public string $headline,
        public array $lines,
        public ?string $quote,
        public string $actionText,
        public string $actionPath,
    ) {}
}
