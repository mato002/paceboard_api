<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TripController;
use App\Http\Controllers\Api\CommunityReportController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\VehicleController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\DeviceController;
use App\Http\Controllers\Api\RouteController;
use App\Http\Controllers\Api\ChallengeController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SocialController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PhoneVerificationController;
use App\Http\Controllers\Api\ExploreController;
use App\Http\Controllers\Api\FeedController;
use App\Http\Controllers\Api\FuelController;
use App\Http\Controllers\Api\TripPhotoController;
use App\Http\Controllers\Api\TripExtrasController;
use App\Http\Controllers\Api\TelemetryController;
use App\Http\Controllers\Api\OfflineSyncController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\SosController;

Route::get('/share/{token}', [TripExtrasController::class, 'showShared']);

Route::get('/docs', [\App\Http\Controllers\Api\DocsController::class, 'index']);
Route::get('/docs/openapi.json', [\App\Http\Controllers\Api\DocsController::class, 'openapi']);

Route::middleware('throttle:auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/forgot-password', [PasswordResetController::class, 'forgot']);
    Route::post('/reset-password', [PasswordResetController::class, 'reset']);
    Route::post('/email/resend', [AuthController::class, 'resendVerificationPublic']);
});

Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verifyEmailFromLink'])
    ->middleware(['signed', 'throttle:auth'])
    ->name('verification.verify');

Route::middleware(['auth:sanctum', 'maintenance', 'throttle:api'])->group(function () {
    Route::get('/user', [AuthController::class, 'profile']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/logout-all', [AuthController::class, 'logoutAll']);
    Route::post('/token/refresh', [AuthController::class, 'refreshToken']);
    Route::post('/email/resend', [AuthController::class, 'resendVerification']);
    Route::post('/email/verify', [AuthController::class, 'verifyEmail']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);
    Route::get('/drivers/nearby', [ProfileController::class, 'nearby']);
    Route::get('/drivers/{user}', [ProfileController::class, 'showPublic']);
    Route::get('/drivers/{user}/trips', [ProfileController::class, 'driverTrips']);
    Route::get('/drivers/{user}/photos', [ProfileController::class, 'driverPhotos']);

    Route::post('/phone/send-code', [PhoneVerificationController::class, 'send'])->middleware('throttle:otp');
    Route::post('/phone/verify', [PhoneVerificationController::class, 'verify']);

    Route::get('/feed', [FeedController::class, 'index']);
    Route::get('/explore', [ExploreController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/analytics', [AnalyticsController::class, 'index']);

    Route::get('/account/export', [AccountController::class, 'export']);
    Route::post('/account/privacy-accept', [AccountController::class, 'acceptPrivacy']);
    Route::delete('/account', [AccountController::class, 'destroy']);

    Route::post('/device/fcm-token', [DeviceController::class, 'updateFcmToken']);

    Route::apiResource('vehicles', VehicleController::class);
    Route::get('/vehicles/{vehicle}/service-status', [VehicleController::class, 'serviceStatus']);
    Route::post('/vehicles/{vehicle}/service', [VehicleController::class, 'recordService']);

    Route::get('/fuel-logs', [FuelController::class, 'index']);
    Route::get('/fuel-logs/summary', [FuelController::class, 'summary']);
    Route::post('/fuel-logs', [FuelController::class, 'store']);
    Route::delete('/fuel-logs/{fuelLog}', [FuelController::class, 'destroy']);

    Route::get('/trips/saved', [SocialController::class, 'savedTrips']);
    Route::get('/trips', [TripController::class, 'index']);
    Route::get('/trips/active', [TripController::class, 'active']);
    Route::post('/trips/offline-sync', [OfflineSyncController::class, 'sync']);
    Route::get('/trips/{trip}', [TripController::class, 'show']);
    Route::patch('/trips/{trip}', [TripController::class, 'update']);
    Route::post('/trips/start', [TripController::class, 'start']);
    Route::post('/trips/{trip}/sync', [TripController::class, 'sync'])->middleware('throttle:30,1');
    Route::post('/trips/{trip}/pause', [TripController::class, 'pause']);
    Route::post('/trips/{trip}/resume', [TripController::class, 'resume']);
    Route::post('/trips/{trip}/end', [TripController::class, 'end']);
    Route::get('/trips/{trip}/share', [TripExtrasController::class, 'share']);
    Route::get('/trips/{trip}/weather', [TripExtrasController::class, 'weather']);
    Route::get('/trips/{trip}/analysis', [TripExtrasController::class, 'analysis']);
    Route::get('/trips/{trip}/photos', [TripPhotoController::class, 'index']);
    Route::post('/trips/{trip}/photos', [TripPhotoController::class, 'store']);
    Route::delete('/trips/{trip}/photos/{photo}', [TripPhotoController::class, 'destroy']);
    Route::post('/trips/{trip}/telemetry', [TelemetryController::class, 'sync']);
    Route::get('/trips/{trip}/telemetry', [TelemetryController::class, 'index']);

    Route::get('/routes', [RouteController::class, 'index']);
    Route::get('/routes/{route}', [RouteController::class, 'show']);
    Route::get('/routes/{route}/leaderboard', [RouteController::class, 'leaderboard']);

    Route::get('/reports', [CommunityReportController::class, 'index']);
    Route::get('/reports/nearby', [CommunityReportController::class, 'nearby']);
    Route::post('/reports', [CommunityReportController::class, 'store']);
    Route::post('/reports/{report}/verify', [CommunityReportController::class, 'verify']);
    Route::post('/reports/{report}/dispute', [CommunityReportController::class, 'dispute']);

    Route::get('/leaderboards', [LeaderboardController::class, 'index']);

    Route::get('/challenges', [ChallengeController::class, 'index']);
    Route::get('/challenges/mine', [ChallengeController::class, 'myChallenges']);
    Route::get('/challenges/{challenge}', [ChallengeController::class, 'show']);
    Route::post('/challenges/{challenge}/join', [ChallengeController::class, 'join']);

    Route::get('/search', [SearchController::class, 'index']);

    Route::post('/users/{user}/follow', [SocialController::class, 'follow']);
    Route::delete('/users/{user}/follow', [SocialController::class, 'unfollow']);
    Route::get('/users/{user}/followers', [SocialController::class, 'followers']);
    Route::get('/users/{user}/following', [SocialController::class, 'following']);
    Route::post('/trips/{trip}/save', [SocialController::class, 'saveTrip']);
    Route::delete('/trips/{trip}/save', [SocialController::class, 'unsaveTrip']);
    Route::post('/trips/{trip}/like', [SocialController::class, 'likeTrip']);
    Route::delete('/trips/{trip}/like', [SocialController::class, 'unlikeTrip']);
    Route::get('/trips/{trip}/comments', [SocialController::class, 'tripComments']);
    Route::post('/trips/{trip}/comments', [SocialController::class, 'commentTrip']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    Route::post('/sos', [SosController::class, 'trigger']);

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats']);
        Route::get('/users', [AdminController::class, 'users']);
        Route::patch('/users/{user}/status', [AdminController::class, 'updateUserStatus']);
        Route::post('/users/{user}/roles', [AdminController::class, 'assignRole']);
        Route::get('/roles', [AdminController::class, 'roles']);
        Route::get('/trips', [AdminController::class, 'trips']);
        Route::delete('/trips/{trip}', [AdminController::class, 'deleteTrip']);
        Route::get('/routes', [AdminController::class, 'routes']);
        Route::get('/challenges', [AdminController::class, 'challenges']);
        Route::post('/challenges', [AdminController::class, 'createChallenge']);
        Route::get('/settings', [AdminController::class, 'settings']);
        Route::put('/settings', [AdminController::class, 'updateSettings']);
        Route::post('/leaderboards/reset', [AdminController::class, 'resetLeaderboards']);
        Route::post('/broadcast', [AdminController::class, 'broadcast']);
        Route::get('/sos-alerts', [AdminController::class, 'sosAlerts']);
        Route::patch('/sos-alerts/{alert}/resolve', [AdminController::class, 'resolveSos']);
        Route::get('/activity-logs', [AdminController::class, 'activityLogs']);
        Route::patch('/reports/{report}/deactivate', [AdminController::class, 'deactivateReport']);
    });
});
