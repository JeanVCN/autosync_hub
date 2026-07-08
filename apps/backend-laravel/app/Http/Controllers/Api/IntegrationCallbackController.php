<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntegrationCallbackRequest;
use App\Http\Resources\IntegrationLogResource;
use App\Services\IntegrationHub\IntegrationCallbackService;
use Illuminate\Http\JsonResponse;

class IntegrationCallbackController extends Controller
{
    public function __invoke(IntegrationCallbackRequest $request, IntegrationCallbackService $callbackService): JsonResponse
    {
        $log = $callbackService->handle($request->validated());

        return response()->json([
            'message' => 'Integration callback registered.',
            'data' => new IntegrationLogResource($log),
        ]);
    }
}
