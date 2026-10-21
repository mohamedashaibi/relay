<?php

namespace App\Mcp\Tools;

use App\Application\Projects\GetProjectUseCase;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Returns one project with its members and project-level files (briefs, specs, references). Use member ids when assigning work; read files with read_attachment.')]
class GetProjectTool extends RelayTool
{
    protected string $name = 'get_project';

    protected string $title = 'Get project';

    public function handle(Request $request, GetProjectUseCase $project): Response|ResponseFactory
    {
        $data = $request->validate(['project' => ['required', 'string', 'max:20']]);

        return $this->guarded(function () use ($request, $project, $data) {
            $p = $project->execute($this->actor($request), $data['project']);

            return Response::structured([
                'key' => $p['key'],
                'name' => $p['name'],
                'description' => $p['description'],
                'task_counts' => $p['status_counts'],
                'members' => array_map(fn ($m) => ['id' => $m['id'], 'name' => $m['name'], 'role' => $m['role']], $p['members']),
                'files' => array_map(fn ($a) => $this->fileBrief($a), $p['attachments']),
            ]);
        });
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project' => $schema->string()->description('Project key, e.g. "WEB".')->required(),
        ];
    }
}
