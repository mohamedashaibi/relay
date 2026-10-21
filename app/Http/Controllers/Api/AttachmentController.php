<?php

namespace App\Http\Controllers\Api;

use App\Application\Attachments\AttachmentTarget;
use App\Application\Attachments\DeleteAttachmentUseCase;
use App\Application\Attachments\DownloadAttachmentUseCase;
use App\Application\Attachments\IncomingFile;
use App\Application\Attachments\ListAttachmentsUseCase;
use App\Application\Attachments\UploadAttachmentUseCase;
use App\Domain\Attachments\AllowedTypes;
use App\Domain\Attachments\AttachmentKind;
use App\Domain\Attachments\AttachmentView;
use App\Http\Controllers\Controller;
use App\Http\Requests\UploadAttachmentRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function indexForTask(Request $request, string $task, ListAttachmentsUseCase $list): JsonResponse
    {
        return $this->listing($list->execute($this->actor($request), AttachmentTarget::task($task)));
    }

    public function storeForTask(UploadAttachmentRequest $request, string $task, UploadAttachmentUseCase $upload): JsonResponse
    {
        return $this->stored($upload->execute($this->actor($request), AttachmentTarget::task($task), $this->incoming($request)));
    }

    public function indexForProject(Request $request, string $project, ListAttachmentsUseCase $list): JsonResponse
    {
        return $this->listing($list->execute($this->actor($request), AttachmentTarget::project($project)));
    }

    public function storeForProject(UploadAttachmentRequest $request, string $project, UploadAttachmentUseCase $upload): JsonResponse
    {
        return $this->stored($upload->execute($this->actor($request), AttachmentTarget::project($project), $this->incoming($request)));
    }

    /** Always a download, except raster images when ?inline=1 (used for thumbnails). */
    public function download(Request $request, int $attachment, DownloadAttachmentUseCase $download): StreamedResponse
    {
        ['attachment' => $file, 'disk' => $disk, 'path' => $path] = $download->execute($this->actor($request), $attachment);
        $inline = $request->boolean('inline') && $file->kind === AttachmentKind::Image;

        return Storage::disk($disk)->response($path, $file->name, [
            'Content-Type' => $file->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
            'Cache-Control' => 'private, max-age=300',
        ], $inline ? 'inline' : 'attachment');
    }

    public function destroy(Request $request, int $attachment, DeleteAttachmentUseCase $delete): JsonResponse
    {
        $delete->execute($this->actor($request), $attachment);

        return response()->json(null, 204);
    }

    private function incoming(UploadAttachmentRequest $request): IncomingFile
    {
        $file = $request->file('file');

        return new IncomingFile($file->getClientOriginalName(), $request->storedMime(), (int) $file->getSize(), $file->getRealPath());
    }

    /** @param list<AttachmentView> $attachments */
    private function listing(array $attachments): JsonResponse
    {
        return response()->json([
            'data' => array_map(fn (AttachmentView $a) => $a->toArray(), $attachments),
            'limits' => [
                'max_bytes' => UploadAttachmentRequest::maxBytes(),
                'extensions' => array_keys(AllowedTypes::ALL),
            ],
        ]);
    }

    private function stored(AttachmentView $attachment): JsonResponse
    {
        return response()->json(['data' => $attachment->toArray()], 201);
    }
}
