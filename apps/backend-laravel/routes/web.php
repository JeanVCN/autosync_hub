<?php

use App\Http\Controllers\Web\VehiclePageController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/vehicles');
Route::get('/vehicles', [VehiclePageController::class, 'index'])->name('web.vehicles.index');
Route::get('/vehicles/create', [VehiclePageController::class, 'create'])->name('web.vehicles.create');
Route::post('/vehicles', [VehiclePageController::class, 'store'])->name('web.vehicles.store');
Route::get('/vehicles/{vehicle}', [VehiclePageController::class, 'show'])->name('web.vehicles.show');
Route::get('/vehicles/{vehicle}/edit', [VehiclePageController::class, 'edit'])->name('web.vehicles.edit');
Route::put('/vehicles/{vehicle}', [VehiclePageController::class, 'update'])->name('web.vehicles.update');
Route::delete('/vehicles/{vehicle}', [VehiclePageController::class, 'destroy'])->name('web.vehicles.destroy');
Route::post('/vehicles/{vehicle}/sync', [VehiclePageController::class, 'sync'])->name('web.vehicles.sync');
