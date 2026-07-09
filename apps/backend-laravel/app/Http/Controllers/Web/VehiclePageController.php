<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\IntegrationHub\VehicleIntegrationSummaryService;
use Illuminate\Contracts\View\View;

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

    public function show(Vehicle $vehicle, VehicleIntegrationSummaryService $summaryService): View
    {
        $vehicle->load(['integrationLogs' => fn ($query) => $query->latest('last_attempt_at')->latest()]);
        $integrationSummary = $summaryService->summarize($vehicle);

        return view('vehicles.show', compact('vehicle', 'integrationSummary'));
    }
}
