<?php

use App\Http\Controllers\Web\VehiclePageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/vehicles');
Route::get('/vehicles', [VehiclePageController::class, 'index'])->name('vehicles.index');
Route::get('/vehicles/{vehicle}', [VehiclePageController::class, 'show'])->name('vehicles.show');
