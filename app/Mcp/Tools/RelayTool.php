<?php

namespace App\Mcp\Tools;

use App\Domain\Attachments\AttachmentView;
use App\Domain\Shared\Actor;
use App\Domain\Shared\DomainException;
use App\Domain\Tasks\TaskView;
use Closure;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;
use Laravel\Passport\Token;

/** Shared plumbing: who is calling, business-rule errors as tool errors, and a compact task shape for LLM context. */
abstract class RelayTool extends Tool
{
    protected function actor(Request $request): Actor
    {
        $user = $request->user();
        $tokenId = $user->token()?->oauth_access_token_id ?? null;
        $token = $tokenId ? Token::with('client:id,name')->find($tokenId) : null;

        return Actor::agent($user->id, $token?->name ?: $token?->client?->name ?: 'AI agent');
    }

    protected function guarded(Closure $work): Response|ResponseFactory
    {
        try {
            return $work();
        } catch (DomainException $e) {
            return Response::error($e->getMessage());
        }
    }

    protected function brief(TaskView $t): array
    {
        return array_filter([
            'key' => $t->key,
            'title' => $t->title,
            'status' => $t->status->value,
            'priority' => $t->priority->value,
            'project' => $t->project['key'],
            'assignee' => $t->assignee['name'] ?? null,
            'reviewer' => $t->reviewer['name'] ?? null,
            'due_date' => $t->dueDate,
            'labels' => $t->labels ?: null,
            'url' => url("/tasks/{$t->key}"),
        ], fn ($v) => $v !== null);
    }

    /** Compact file listing: enough for the model to decide whether to call read_attachment. */
    protected function fileBrief(AttachmentView|array $file): array
    {
        $f = $file instanceof AttachmentView ? $file->toArray() : $file;
        $readable = $f['kind'] === 'image' || $f['has_text'];

        return [
            'attachment_id' => $f['id'],
            'name' => $f['name'],
            'type' => $f['kind'],
            'size_kb' => (int) ceil($f['size'] / 1024),
            'readable' => $readable,
            'uploaded_by' => $f['uploader']['name'] ?? null,
            'uploaded_at' => $f['created_at'],
        ];
    }

    /** @param list<TaskView> $tasks */
    protected function briefs(array $tasks): array
    {
        return array_map(fn (TaskView $t) => $this->brief($t), $tasks);
    }
}
