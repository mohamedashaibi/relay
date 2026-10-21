<?php

namespace App\Domain\Tasks;

final readonly class TaskView
{
    /**
     * @param  array{id:int,key:string,name:string,color:string}  $project
     * @param  list<string>  $labels
     * @param  array{id:int,name:string}|null  $assignee
     * @param  array{id:int,name:string}|null  $reporter
     * @param  array{id:int,name:string}|null  $reviewer
     */
    public function __construct(
        public int $id,
        public string $key,
        public int $number,
        public array $project,
        public string $title,
        public ?string $description,
        public TaskStatus $status,
        public TaskPriority $priority,
        public array $labels,
        public ?array $assignee,
        public ?array $reporter,
        public ?array $reviewer,
        public ?string $reviewNote,
        public ?string $dueDate,
        public float $position,
        public ?string $completedAt,
        public string $createdAt,
        public string $updatedAt,
        public int $commentCount = 0,
        public int $attachmentCount = 0,
    ) {}

    public function projectId(): int
    {
        return $this->project['id'];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'number' => $this->number,
            'project' => $this->project,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'priority' => $this->priority->value,
            'labels' => $this->labels,
            'assignee' => $this->assignee,
            'reporter' => $this->reporter,
            'reviewer' => $this->reviewer,
            'review_note' => $this->reviewNote,
            'due_date' => $this->dueDate,
            'position' => $this->position,
            'completed_at' => $this->completedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'comment_count' => $this->commentCount,
            'attachment_count' => $this->attachmentCount,
        ];
    }
}
