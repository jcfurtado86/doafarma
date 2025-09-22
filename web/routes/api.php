<?php

declare(strict_types = 1);

use App\Http\Controllers\Api\V1\Drug;
use App\Http\Controllers\Api\V1\MedicationOffering;
use App\Http\Controllers\Auth\DoctorRegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', fn (Request $request) => $request->user());

Route::post('/register/doctor', DoctorRegistrationController::class)
    ->middleware('guest')
    ->name('doctor.register');

Route::prefix('v1')->group(function (): void {
    Route::prefix('drugs')->group(function (): void {
        Route::get('search', Drug\SearchController::class)
            ->name('api.v1.drugs.search')
            ->middleware('auth:sanctum');
    });

    Route::prefix('medication-offerings')->group(function (): void {
        Route::post('/', MedicationOffering\StoreController::class)
            ->name('api.v1.medication-offerings.post')
            ->middleware('auth:sanctum');
    });
});
