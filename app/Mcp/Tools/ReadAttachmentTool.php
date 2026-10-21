<?php

namespace App\Mcp\Tools;

use App\Application\Attachments\ReadAttachmentUseCase;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Reads a file attached to a task or project (ids come from get_task or get_project). Text, Markdown, CSV, JSON, PDF, Word and PowerPoint files return their text — long files come in pages, continue with next_offset. Images are returned as images.')]
class ReadAttachmentTool extends RelayTool
{
    protected string $name = 'read_attachment';

    protected string $title = 'Read attachment';

    public function handle(Request $request, ReadAttachmentUseCase $read): Response|ResponseFactory
    {
        $data = $request->validate([
            'attachment_id' => ['required', 'integer', 'min:1'],
            'offset' => ['nullable', 'integer', 'min:0'],
            'max_chars' => ['nullable', 'integer', 'min:1000', 'max:50000'],
        ]);

        return $this->guarded(function () use ($request, $read, $data) {
            $content = $read->execute($this->actor($request), (int) $data['attachment_id'], (int) ($data['offset'] ?? 0), (int) ($data['max_chars'] ?? 20_000));
            $file = $content->attachment;

            if ($content->imageBytes !== null) {
                return Response::make([
                    Response::text("Image “{$file->name}” ({$file->mime}, ".ceil($file->size / 1024).' KB):'),
                    Response::image($content->imageBytes, $file->mime),
                ]);
            }

            if ($content->text === null) {
                return Response::text("“{$file->name}”: {$content->unavailableReason}");
            }

            $end = $content->offset + mb_strlen($content->text);
            $header = "File “{$file->name}” — characters {$content->offset}–{$end} of {$file->textLength}.";
            $footer = $content->nextOffset !== null
                ? "\n\n[More text follows. Call read_attachment with attachment_id={$file->id} and offset={$content->nextOffset} to continue.]"
                : "\n\n[End of file.]";

            return Response::text($header."\n\n".$content->text.$footer);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'attachment_id' => $schema->integer()->description('From the attachments/files list of get_task or get_project.')->required(),
            'offset' => $schema->integer()->description('Character offset to start from, for paging through long files. Defaults to 0.'),
            'max_chars' => $schema->integer()->description('Characters to return, 1,000–50,000. Defaults to 20,000.'),
        ];
    }
}
