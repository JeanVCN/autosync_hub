<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\SyncVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Http\Resources\IntegrationLogResource;
use App\Http\Resources\IntegrationSummaryResource;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Services\IntegrationHub\VehicleIntegrationSummaryService;
use App\Services\IntegrationHub\VehicleSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class VehicleController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return VehicleResource::collection(
            Vehicle::query()
                ->latest()
                ->paginate(15)
        );
    }

    public function store(StoreVehicleRequest $request): JsonResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return (new VehicleResource($vehicle))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(Vehicle $vehicle): VehicleResource
    {
        return new VehicleResource($vehicle->load('integrationLogs'));
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): VehicleResource
    {
        $vehicle->update($request->validated());

        return new VehicleResource($vehicle->refresh());
    }

    public function destroy(Vehicle $vehicle): JsonResponse
    {
        $vehicle->delete();

        return response()->json(['message' => 'Vehicle deleted.']);
    }

    public function sync(SyncVehicleRequest $request, Vehicle $vehicle, VehicleSyncService $syncService): JsonResponse
    {
        $response = $syncService->sync($vehicle, $request->validated('providers') ?? []);

        return response()->json([
            'message' => 'Vehicle synchronization requested.',
            'data' => $response,
        ]);
    }

    public function integrationLogs(Vehicle $vehicle): AnonymousResourceCollection
    {
        return IntegrationLogResource::collection(
            $vehicle->integrationLogs()
                ->latest('last_attempt_at')
                ->latest()
                ->paginate(20)
        );
    }

    public function integrationSummary(Vehicle $vehicle, VehicleIntegrationSummaryService $summaryService): AnonymousResourceCollection
    {
        return IntegrationSummaryResource::collection($summaryService->summarize($vehicle));
    }
}
