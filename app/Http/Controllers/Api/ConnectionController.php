<?php

namespace App\Http\Controllers\Api;

use App\Application\Tokens\IssueAccessTokenUseCase;
use App\Application\Tokens\ListConnectionsUseCase;
use App\Application\Tokens\RevokeAccessTokenUseCase;
use App\Http\Controllers\Controller;
use App\Http\Requests\IssueTokenRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** AI connections: personal tokens for Claude Code / Desktop, plus OAuth grants from Claude.ai / ChatGPT. */
class ConnectionController extends Controller
{
    public function index(Request $request, ListConnectionsUseCase $list): JsonResponse
    {
        return response()->json([
            'data' => $list->execute($this->actor($request)),
            'mcp_url' => url('/mcp'),
        ]);
    }

    public function store(IssueTokenRequest $request, IssueAccessTokenUseCase $issue): JsonResponse
    {
        return response()->json(['data' => $issue->execute($this->actor($request), $request->validated('name'))], 201);
    }

    public function destroy(Request $request, string $connection, RevokeAccessTokenUseCase $revoke): JsonResponse
    {
        $revoke->execute($this->actor($request), $connection);

        return response()->json(null, 204);
    }
}
