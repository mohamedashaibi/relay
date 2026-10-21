<?php

namespace App\Mcp\Tools;

use App\Application\Attachments\AttachmentTarget;
use App\Application\Attachments\IncomingFile;
use App\Application\Attachments\UploadAttachmentUseCase;
use App\Domain\Attachments\AllowedTypes;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;

#[Description('Saves a text file you wrote — a report, plan, research notes, CSV export, JSON data — as an attachment on a task or project, so people (and later assistants) can open it. Use it for outputs too long for a comment. Formats: md, txt, csv, json, xml, yaml, log. Max 1 MB.')]
class AttachFileTool extends RelayTool
{
    protected string $name = 'attach_file';

    protected string $title = 'Attach file';

    public const MAX_BYTES = 1024 * 1024;

    public function handle(Request $request, UploadAttachmentUseCase $upload): Response|ResponseFactory
    {
        $data = $request->validate([
            'task' => ['required_without:project', 'prohibits:project', 'nullable', 'string', 'max:30'],
            'project' => ['required_without:task', 'nullable', 'string', 'max:20'],
            'filename' => ['required', 'string', 'max:150'],
            'content' => ['required', 'string', 'max:'.self::MAX_BYTES],
        ], [
            'task.prohibits' => 'Pass either task or project, not both.',
            'content.max' => 'The file is too large. Attach at most 1 MB of text, or split it into several files.',
        ]);

        $filename = trim($data['filename']);
        if (pathinfo($filename, PATHINFO_EXTENSION) === '') {
            $filename .= '.md';
        }
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        if (! in_array($extension, AllowedTypes::TEXT, true)) {
            return Response::error('Assistants can attach text files only: '.implode(', ', AllowedTypes::TEXT).'. Use a .md file for formatted reports.');
        }
        if (strlen($data['content']) > self::MAX_BYTES) {
            return Response::error('The file is too large. Attach at most 1 MB of text, or split it into several files.');
        }

        return $this->guarded(function () use ($request, $upload, $data, $filename, $extension) {
            $path = tempnam(sys_get_temp_dir(), 'relay-mcp-');
            try {
                file_put_contents($path, $data['content']);
                $target = isset($data['task']) ? AttachmentTarget::task($data['task']) : AttachmentTarget::project($data['project']);

                $attachment = $upload->execute(
                    $this->actor($request),
                    $target,
                    new IncomingFile($filename, AllowedTypes::mimeFor($extension), strlen($data['content']), $path),
                );
            } finally {
                @unlink($path);
            }

            return Response::structured([
                'attached' => $this->fileBrief($attachment),
                'to' => $data['task'] ?? $data['project'],
            ]);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'task' => $schema->string()->description('Task key such as "WEB-12". Give this or project, not both.'),
            'project' => $schema->string()->description('Project key such as "WEB", for files about the whole project.'),
            'filename' => $schema->string()->description('e.g. "pricing-research.md" or "q3-leads.csv". Defaults to .md if no extension.')->required(),
            'content' => $schema->string()->description('The full file content as text.')->required(),
        ];
    }
}
