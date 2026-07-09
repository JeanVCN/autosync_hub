<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntegrationCallbackRequest;
use App\Http\Resources\IntegrationLogResource;
use App\Services\IntegrationHub\IntegrationCallbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;

class IntegrationCallbackController extends Controller
{
    public function __invoke(IntegrationCallbackRequest $request, IntegrationCallbackService $callbackService): JsonResponse
    {
        $token = Config::get('services.integration_hub.token');
        if (filled($token) && $request->bearerToken() !== $token) {
            return response()->json([
                'message' => 'Missing or invalid integration callback token.',
            ], 401);
        }

        $log = $callbackService->handle($request->validated());

        return response()->json([
            'message' => 'Integration callback registered.',
            'data' => new IntegrationLogResource($log),
        ]);
    }
}
