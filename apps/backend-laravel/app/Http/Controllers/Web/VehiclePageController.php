<?php

namespace App\Http\Controllers\Web;

use App\Enums\IntegrationProvider;
use App\Enums\VehicleStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Models\Vehicle;
use App\Services\IntegrationHub\VehicleIntegrationSummaryService;
use App\Services\IntegrationHub\VehicleSyncService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class VehiclePageController extends Controller
{
    public function index(): View
    {
        $vehicles = Vehicle::query()
            ->with(['integrationLogs' => fn ($query) => $query->latest('last_attempt_at')->latest()])
            ->orderBy('brand')
            ->paginate(10);

        return view('vehicles.index', compact('vehicles'));
    }

    public function create(): View
    {
        return view('vehicles.create', [
            'vehicle' => new Vehicle([
                'status' => VehicleStatus::Active,
            ]),
            'statuses' => VehicleStatus::values(),
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $vehicle = Vehicle::create($request->validated());

        return redirect()
            ->route('web.vehicles.show', $vehicle)
            ->with('status', 'Vehicle created.');
    }

    public function show(Vehicle $vehicle, VehicleIntegrationSummaryService $summaryService): View
    {
        $vehicle->load(['integrationLogs' => fn ($query) => $query->latest('last_attempt_at')->latest()]);
        $integrationSummary = $summaryService->summarize($vehicle);

        return view('vehicles.show', compact('vehicle', 'integrationSummary'));
    }

    public function edit(Vehicle $vehicle): View
    {
        return view('vehicles.edit', [
            'vehicle' => $vehicle,
            'statuses' => VehicleStatus::values(),
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($request->validated());

        return redirect()
            ->route('web.vehicles.show', $vehicle)
            ->with('status', 'Vehicle updated.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return redirect()
            ->route('web.vehicles.index')
            ->with('status', 'Vehicle deleted.');
    }

    public function sync(Vehicle $vehicle, VehicleSyncService $syncService): RedirectResponse
    {
        $syncService->sync($vehicle, IntegrationProvider::values());

        return redirect()
            ->route('web.vehicles.show', $vehicle)
            ->with('status', 'Vehicle synchronization requested.');
    }
}
