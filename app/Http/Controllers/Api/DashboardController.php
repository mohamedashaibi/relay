<?php

namespace App\Http\Controllers\Api;

use App\Application\Dashboard\GetDashboardUseCase;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GetDashboardUseCase $dashboard): JsonResponse
    {
        return response()->json($dashboard->execute($this->actor($request)));
    }
}
