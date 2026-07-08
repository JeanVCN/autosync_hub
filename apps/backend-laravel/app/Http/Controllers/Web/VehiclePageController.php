<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
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

    public function show(Vehicle $vehicle): View
    {
        $vehicle->load(['integrationLogs' => fn ($query) => $query->latest('last_attempt_at')->latest()]);

        return view('vehicles.show', compact('vehicle'));
    }
}
