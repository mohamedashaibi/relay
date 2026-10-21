<?php

namespace App\Http\Controllers\Api;

use App\Application\Projects\CreateProjectUseCase;
use App\Application\Projects\DeleteProjectUseCase;
use App\Application\Projects\Dto\CreateProjectInput;
use App\Application\Projects\Dto\UpdateProjectInput;
use App\Application\Projects\GetProjectUseCase;
use App\Application\Projects\ListProjectsUseCase;
use App\Application\Projects\UpdateProjectUseCase;
use App\Domain\Projects\ProjectView;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request, ListProjectsUseCase $list): JsonResponse
    {
        $request->validate(['archived' => ['nullable', 'boolean']]);

        return response()->json([
            'data' => array_map(fn (ProjectView $p) => $p->toArray(), $list->execute($this->actor($request), $request->boolean('archived'))),
        ]);
    }

    public function store(StoreProjectRequest $request, CreateProjectUseCase $create): JsonResponse
    {
        $project = $create->execute($this->actor($request), CreateProjectInput::fromArray($request->validated()));

        return response()->json(['data' => $project->toArray()], 201);
    }

    public function show(Request $request, string $project, GetProjectUseCase $get): JsonResponse
    {
        return response()->json(['data' => $get->execute($this->actor($request), $project)]);
    }

    public function update(UpdateProjectRequest $request, string $project, UpdateProjectUseCase $update): JsonResponse
    {
        $updated = $update->execute($this->actor($request), $project, UpdateProjectInput::fromArray($request->validated()));

        return response()->json(['data' => $updated->toArray()]);
    }

    public function destroy(Request $request, string $project, DeleteProjectUseCase $delete): JsonResponse
    {
        $delete->execute($this->actor($request), $project);

        return response()->json(null, 204);
    }
}
