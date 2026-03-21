<?php

use App\Http\Controllers\Auth\AdminAuthController;
use App\Http\Controllers\Auth\CompanyAuthController;
use App\Http\Controllers\Auth\DoctorAuthController;
use App\Http\Controllers\Auth\MedicalRepAuthController;
use App\Http\Controllers\Admin\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/admin/register', [AdminAuthController::class, 'register']);
    Route::post('/admin/login', [AdminAuthController::class, 'login']);
    Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->middleware('auth:admin-api');
    Route::get('/admin/me', [AdminAuthController::class, 'me'])->middleware('auth:admin-api');

    Route::post('/company/register', [CompanyAuthController::class, 'register']);
    Route::post('/company/login', [CompanyAuthController::class, 'login']);
    Route::post('/company/logout', [CompanyAuthController::class, 'logout'])->middleware('auth:company-api');
    Route::get('/company/me', [CompanyAuthController::class, 'me'])->middleware('auth:company-api');

    Route::post('/doctor/register', [DoctorAuthController::class, 'register']);
    Route::post('/doctor/login', [DoctorAuthController::class, 'login']);
    Route::post('/doctor/logout', [DoctorAuthController::class, 'logout'])->middleware('auth:doctor-api');
    Route::get('/doctor/me', [DoctorAuthController::class, 'me'])->middleware('auth:doctor-api');

    Route::post('/rep/register', [MedicalRepAuthController::class, 'register']);
    Route::post('/rep/login', [MedicalRepAuthController::class, 'login']);
    Route::post('/rep/logout', [MedicalRepAuthController::class, 'logout'])->middleware('auth:rep-api');
    Route::get('/rep/me', [MedicalRepAuthController::class, 'me'])->middleware('auth:rep-api');
});

Route::prefix('admin')->middleware('auth:admin-api')->group(function () {
    Route::get('/users/pending', [UserManagementController::class, 'index']);
    Route::post('/users/{type}/{id}/approve', [UserManagementController::class, 'approve']);
    Route::post('/users/{type}/{id}/block', [UserManagementController::class, 'block']);
});

