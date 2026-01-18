<?php

declare(strict_types = 1);

use App\Http\Controllers\Api\V1\Auth;
use App\Http\Controllers\Api\V1\Drug;
use App\Http\Controllers\Api\V1\MedicationAppointment;
use App\Http\Controllers\Api\V1\MedicationOffering;
use App\Http\Controllers\Api\V1\MedicationRequest;
use App\Http\Controllers\Auth\ApiLoginController;
use App\Http\Controllers\Auth\DoctorRegistrationController;
use App\Http\Controllers\Auth\ReceptorRegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', fn (Request $request) => $request->user());

Route::post('/login', ApiLoginController::class)
    ->middleware('guest')
    ->name('api.login');

Route::post('/register/doctor', DoctorRegistrationController::class)
    ->middleware('guest')
    ->name('doctor.register');

Route::post('/register/receptor', ReceptorRegistrationController::class)
    ->middleware('guest')
    ->name('receptor.register');

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('login', Auth\LoginController::class)
            ->middleware('guest')
            ->name('api.v1.auth.login');
    });

    Route::prefix('drugs')->group(function (): void {
        Route::get('search', Drug\SearchController::class)
            ->name('api.v1.drugs.search')
            ->middleware('auth:sanctum');
        Route::get('/', Drug\ListController::class)
            ->name('api.v1.drugs.list')
            ->middleware('auth:sanctum');
    });

    Route::prefix('medication-offerings')->group(function (): void {
        Route::get('/search', MedicationOffering\SearchController::class)
            ->name('api.v1.medication-offerings.search')
            ->middleware('auth:sanctum');
        Route::get('/', MedicationOffering\ListController::class)
            ->name('api.v1.medication-offerings.list')
            ->middleware('auth:sanctum');
        Route::post('/', MedicationOffering\StoreController::class)
            ->name('api.v1.medication-offerings.post')
            ->middleware('auth:sanctum');
        Route::get('/{medicationOffering}', MedicationOffering\ShowController::class)
            ->name('api.v1.medication-offerings.show')
            ->middleware('auth:sanctum');
        Route::put('/{medicationOffering}', MedicationOffering\UpdateController::class)
            ->name('api.v1.medication-offerings.put')
            ->middleware('auth:sanctum');
        Route::delete('/{medicationOffering}', MedicationOffering\DeleteController::class)
            ->name('api.v1.medication-offerings.delete')
            ->middleware('auth:sanctum');
    });

    Route::prefix('medication-requests')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', MedicationRequest\ListController::class)
            ->name('api.v1.medication-requests.list');
        Route::post('/', MedicationRequest\StoreController::class)
            ->name('api.v1.medication-requests.store');
        Route::get('/received', MedicationRequest\ReceivedController::class)
            ->name('api.v1.medication-requests.received');
        Route::patch('/{medicationRequest}/confirm', MedicationRequest\ConfirmController::class)
            ->name('api.v1.medication-requests.confirm');
        Route::patch('/{medicationRequest}/reject', MedicationRequest\RejectController::class)
            ->name('api.v1.medication-requests.reject');
    });

    Route::prefix('medication-appointments')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', MedicationAppointment\ListController::class)
            ->name('api.v1.medication-appointments.list');
        Route::post('/', MedicationAppointment\StoreController::class)
            ->name('api.v1.medication-appointments.store');
        Route::get('/received', MedicationAppointment\ReceivedController::class)
            ->name('api.v1.medication-appointments.received');
        Route::patch(
            '/{medicationAppointment}/confirm-delivery-receptor',
            MedicationAppointment\ConfirmDeliveryReceptorController::class
        )
            ->name('api.v1.medication-appointments.confirm-delivery-receptor');
        Route::patch(
            '/{medicationAppointment}/confirm-delivery-doctor',
            MedicationAppointment\ConfirmDeliveryDoctorController::class
        )
            ->name('api.v1.medication-appointments.confirm-delivery-doctor');
        Route::patch(
            '/{medicationAppointment}/accept',
            MedicationAppointment\AcceptController::class
        )
            ->name('api.v1.medication-appointments.accept');
        Route::patch(
            '/{medicationAppointment}/counter-propose',
            MedicationAppointment\CounterProposeController::class
        )
            ->name('api.v1.medication-appointments.counter-propose');
    });
});
