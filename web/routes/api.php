<?php

declare(strict_types = 1);

use App\Http\Controllers\Auth\DoctorRegistrationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', fn (Request $request) => $request->user());

Route::post('/register/doctor', DoctorRegistrationController::class)
    ->middleware('guest')
    ->name('doctor.register');
