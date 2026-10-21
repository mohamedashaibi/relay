<?php

namespace App\Mcp\Tools;

use App\Application\Projects\ListProjectsUseCase;
use App\Domain\Projects\ProjectView;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
#[Description('Lists the projects the current user belongs to, with their keys and task counts per status. Project keys prefix task keys (project WEB → task WEB-12).')]
class ListProjectsTool extends RelayTool
{
    protected string $name = 'list_projects';

    protected string $title = 'List projects';

    public function handle(Request $request, ListProjectsUseCase $projects): Response|ResponseFactory
    {
        $request->validate(['include_archived' => ['nullable', 'boolean']]);

        return $this->guarded(fn () => Response::structured([
            'projects' => array_map(fn (ProjectView $p) => [
                'key' => $p->key,
                'name' => $p->name,
                'description' => $p->description,
                'your_role' => $p->role?->value,
                'archived' => $p->archived,
                'task_counts' => $p->statusCounts,
            ], $projects->execute($this->actor($request), $request->boolean('include_archived'))),
        ]));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'include_archived' => $schema->boolean()->description('Include archived projects. Defaults to false.'),
        ];
    }
}
