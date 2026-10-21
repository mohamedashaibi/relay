<?php

namespace App\Application\Tasks;

use App\Domain\Attachments\AttachmentView;
use App\Domain\Tasks\ActivityView;
use App\Domain\Tasks\CommentView;
use App\Domain\Tasks\TaskView;

final readonly class TaskDetail
{
    /**
     * @param  list<CommentView>  $comments
     * @param  list<ActivityView>  $activity
     * @param  list<AttachmentView>  $attachments
     */
    public function __construct(
        public TaskView $task,
        public array $comments,
        public array $activity,
        public bool $canReview,
        public bool $canDelete,
        public array $attachments = [],
    ) {}

    public function toArray(): array
    {
        return [
            ...$this->task->toArray(),
            'comments' => array_map(fn (CommentView $c) => $c->toArray(), $this->comments),
            'activity' => array_map(fn (ActivityView $a) => $a->toArray(), $this->activity),
            'attachments' => array_map(fn (AttachmentView $a) => $a->toArray(), $this->attachments),
            'permissions' => ['review' => $this->canReview, 'delete' => $this->canDelete],
        ];
    }
}
