<?php

declare(strict_types=1);

use App\Http\Controllers\Api\BedController;
use App\Http\Controllers\Api\BedOccupationController;
use App\Http\Controllers\Api\PatientBedController;
use App\Http\Controllers\Api\TransferController;
use Illuminate\Support\Facades\Route;

Route::get('beds', [BedController::class, 'index']);
Route::get('beds/{bed}', [BedController::class, 'show']);
Route::get('beds/{bed}/occupations', [BedController::class, 'history']);

Route::post('beds/{bed}/occupation', [BedOccupationController::class, 'store']);
Route::delete('beds/{bed}/occupation', [BedOccupationController::class, 'destroy']);

Route::post('transfers', [TransferController::class, 'store']);

Route::get('patients/{cpf}/bed', [PatientBedController::class, 'show']);
