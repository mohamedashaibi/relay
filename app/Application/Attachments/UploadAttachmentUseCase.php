<?php

namespace App\Application\Attachments;

use App\Domain\Attachments\AttachmentKind;
use App\Domain\Attachments\AttachmentRepository;
use App\Domain\Attachments\AttachmentView;
use App\Domain\Shared\Actor;
use App\Domain\Tasks\ActivityType;
use App\Domain\Tasks\TaskRepository;

/** Stores the file privately and extracts its text once, so assistants can read it cheaply later. */
final class UploadAttachmentUseCase
{
    public function __construct(
        private readonly AttachmentRepository $attachments,
        private readonly AttachmentLocator $locator,
        private readonly AttachmentStorage $storage,
        private readonly TextExtractor $extractor,
        private readonly TaskRepository $tasks,
    ) {}

    public function execute(Actor $actor, AttachmentTarget $target, IncomingFile $file): AttachmentView
    {
        ['projectId' => $projectId, 'taskId' => $taskId] = $this->locator->target($actor, $target);

        $name = $this->cleanName($file->name);
        $text = $this->extractor->extract($file->localPath, AttachmentKind::detect($file->mime, $name), $name);
        $stored = $this->storage->put($file->localPath, $file->extension());

        $attachment = $this->attachments->create(
            $projectId, $taskId, $actor, $name, $file->mime, $file->size, $stored['disk'], $stored['path'], $text,
        );

        if ($taskId !== null) {
            $this->tasks->log($taskId, $actor, ActivityType::Attached, ['name' => $name]);
        }

        return $attachment;
    }

    /** Keeps the user's filename readable but strips path parts and control characters. */
    private function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = trim((string) preg_replace('/[\x00-\x1F\x7F]+/u', '', $name));

        return mb_substr($name !== '' ? $name : 'file', 0, 200);
    }
}
