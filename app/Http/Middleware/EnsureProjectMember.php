<?php

namespace App\Http\Middleware;

use App\Domain\Projects\ProjectRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Route-level RBAC gate for /projects/{project}/… — use cases re-check at the object level. */
class EnsureProjectMember
{
    public function __construct(private readonly ProjectRepository $projects) {}

    public function handle(Request $request, Closure $next): Response
    {
        $ref = (string) $request->route('project');
        $id = ctype_digit($ref) ? (int) $ref : $this->projects->idForKey(strtoupper($ref));

        if ($id === null || $this->projects->roleOf($id, $request->user()->id) === null) {
            return response()->json(['message' => 'Project not found.'], 404);
        }

        return $next($request);
    }
}
