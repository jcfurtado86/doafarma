<?php

declare(strict_types = 1);

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\Auth;
use App\Http\Controllers\Api\V1\DoctorRating;
use App\Http\Controllers\Api\V1\Drug;
use App\Http\Controllers\Api\V1\MedicationAppointment;
use App\Http\Controllers\Api\V1\MedicationOffering;
use App\Http\Controllers\Api\V1\MedicationRequest;
use App\Http\Controllers\Api\V1\PushToken;
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

    // Routes that require authentication AND approval
    Route::middleware(['auth:sanctum', 'approved'])->group(function (): void {
        Route::prefix('drugs')->group(function (): void {
            Route::get('search', Drug\SearchController::class)
                ->name('api.v1.drugs.search');
            Route::get('/', Drug\ListController::class)
                ->name('api.v1.drugs.list');
        });

        Route::prefix('medication-offerings')->group(function (): void {
            Route::get('/search', MedicationOffering\SearchController::class)
                ->name('api.v1.medication-offerings.search');
            Route::get('/', MedicationOffering\ListController::class)
                ->name('api.v1.medication-offerings.list');
            Route::post('/', MedicationOffering\StoreController::class)
                ->name('api.v1.medication-offerings.post');
            Route::get('/{medicationOffering}', MedicationOffering\ShowController::class)
                ->name('api.v1.medication-offerings.show');
            Route::put('/{medicationOffering}', MedicationOffering\UpdateController::class)
                ->name('api.v1.medication-offerings.put');
            Route::delete('/{medicationOffering}', MedicationOffering\DeleteController::class)
                ->name('api.v1.medication-offerings.delete');
        });

        Route::prefix('medication-requests')->group(function (): void {
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

        Route::prefix('medication-appointments')->group(function (): void {
            Route::get('/', MedicationAppointment\ListController::class)
                ->name('api.v1.medication-appointments.list');
            Route::post('/', MedicationAppointment\StoreController::class)
                ->name('api.v1.medication-appointments.store');
            Route::get('/history', MedicationAppointment\HistoryController::class)
                ->name('api.v1.medication-appointments.history');
            Route::get('/doctor-history', MedicationAppointment\DoctorHistoryController::class)
                ->name('api.v1.medication-appointments.doctor-history');
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

        Route::prefix('push-tokens')->group(function (): void {
            Route::post('/', PushToken\RegisterController::class)
                ->name('api.v1.push-tokens.register');
            Route::delete('/', PushToken\DeleteController::class)
                ->name('api.v1.push-tokens.delete');
        });

        Route::prefix('doctor-ratings')->group(function (): void {
            Route::get('/my-ratings', DoctorRating\MyRatingsController::class)
                ->name('api.v1.doctor-ratings.my-ratings');
            Route::post('/{medicationAppointment}', DoctorRating\StoreController::class)
                ->name('api.v1.doctor-ratings.store');
            Route::get('/appointment/{medicationAppointment}', DoctorRating\ShowByAppointmentController::class)
                ->name('api.v1.doctor-ratings.show-by-appointment');
            Route::patch('/{doctorRating}', DoctorRating\UpdateController::class)
                ->name('api.v1.doctor-ratings.update');
            Route::get('/doctor/{doctor}', DoctorRating\ListController::class)
                ->name('api.v1.doctor-ratings.list');
        });
    });

    // Admin routes - require authentication and admin role
    Route::prefix('admin')->middleware(['auth:sanctum', 'admin'])->group(function (): void {
        Route::prefix('users')->group(function (): void {
            Route::get('/', [Admin\UserManagementController::class, 'index'])
                ->name('api.v1.admin.users.index');
            Route::get('/pending', [Admin\UserManagementController::class, 'pending'])
                ->name('api.v1.admin.users.pending');
            Route::get('/stats', [Admin\UserManagementController::class, 'stats'])
                ->name('api.v1.admin.users.stats');
            Route::get('/{user}', [Admin\UserManagementController::class, 'show'])
                ->name('api.v1.admin.users.show');
            Route::patch('/{user}/approve', [Admin\UserManagementController::class, 'approve'])
                ->name('api.v1.admin.users.approve');
            Route::patch('/{user}/reject', [Admin\UserManagementController::class, 'reject'])
                ->name('api.v1.admin.users.reject');
        });
    });
});
