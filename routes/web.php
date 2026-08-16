<?php

use App\Http\Controllers\Admin\WebAdminController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('/admin/login'));

Route::get('/admin/login', [WebAdminController::class, 'showLogin'])->name('login');
Route::post('/admin/login', [WebAdminController::class, 'login'])->middleware('throttle:admin-login');
Route::post('/admin/logout', [WebAdminController::class, 'logout'])->middleware('auth');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [WebAdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/admin/dashboard/panel/{panel}', [WebAdminController::class, 'dashboardPanel'])->where('panel', 'users|reports|trips');
    Route::get('/admin/users', [WebAdminController::class, 'users']);
    Route::patch('/admin/users/{user}/status', [WebAdminController::class, 'updateUserStatus']);
    Route::post('/admin/users/{user}/make-admin', [WebAdminController::class, 'makeAdmin']);
    Route::get('/admin/trips', [WebAdminController::class, 'trips']);
    Route::delete('/admin/trips/{trip}', [WebAdminController::class, 'deleteTrip']);
    Route::get('/admin/settings', [WebAdminController::class, 'settings']);
    Route::put('/admin/settings', [WebAdminController::class, 'updateSettings']);
    Route::post('/admin/leaderboards/reset', [WebAdminController::class, 'resetLeaderboards']);
    Route::post('/admin/broadcast', [WebAdminController::class, 'broadcast']);
    Route::get('/admin/sos', [WebAdminController::class, 'sosAlerts']);
    Route::patch('/admin/sos/{alert}/resolve', [WebAdminController::class, 'resolveSos']);
    Route::get('/admin/challenges', [WebAdminController::class, 'challenges']);
    Route::post('/admin/challenges', [WebAdminController::class, 'createChallenge']);
    Route::patch('/admin/reports/{report}/deactivate', [WebAdminController::class, 'deactivateReport']);
});
