<?php

namespace App\Http\Controllers\Api;

use App\Application\Projects\AddProjectMemberUseCase;
use App\Application\Projects\RemoveProjectMemberUseCase;
use App\Domain\Projects\MemberView;
use App\Domain\Projects\ProjectRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\AddMemberRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectMemberController extends Controller
{
    public function store(AddMemberRequest $request, string $project, AddProjectMemberUseCase $add): JsonResponse
    {
        $data = $request->validated();
        $members = $add->execute($this->actor($request), $project, $data['email'], ProjectRole::from($data['role']));

        return response()->json(['data' => array_map(fn (MemberView $m) => $m->toArray(), $members)], 201);
    }

    public function destroy(Request $request, string $project, int $user, RemoveProjectMemberUseCase $remove): JsonResponse
    {
        $remove->execute($this->actor($request), $project, $user);

        return response()->json(null, 204);
    }
}
