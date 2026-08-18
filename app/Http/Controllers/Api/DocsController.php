<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

class DocsController extends Controller
{
    public function index()
    {
        return view('api.docs', ['endpoints' => $this->endpoints()]);
    }

    public function openapi()
    {
        return response()->json($this->openApiSpec());
    }

    private function endpoints(): array
    {
        return [
            'Authentication' => [
                ['POST', '/api/register', 'Register new user', false],
                ['POST', '/api/login', 'Login (returns Bearer token)', false],
                ['POST', '/api/logout', 'Revoke current token', true],
                ['POST', '/api/logout-all', 'Revoke all tokens', true],
                ['POST', '/api/token/refresh', 'Refresh current token', true],
                ['POST', '/api/email/verify', 'Verify email (id + hash body)', true],
                ['POST', '/api/forgot-password', 'Send password reset email', false],
                ['POST', '/api/reset-password', 'Reset password with token', false],
                ['POST', '/api/email/resend', 'Resend email verification', true],
                ['POST', '/api/phone/send-code', 'Send phone OTP', true],
                ['POST', '/api/phone/verify', 'Verify phone OTP', true],
            ],
            'Profile' => [
                ['GET', '/api/profile', 'Get own profile + achievements', true],
                ['PUT', '/api/profile', 'Update profile', true],
                ['POST', '/api/profile/avatar', 'Upload avatar (multipart)', true],
                ['GET', '/api/users/{id}/avatar', 'Public profile photo', false],
                ['POST', '/api/profile/photos', 'Upload a gallery photo or video', true],
                ['DELETE', '/api/profile/photos/{id}', 'Delete own gallery photo', true],
                ['GET', '/api/drivers/{id}', 'Public driver profile', true],
            ],
            'Trips' => [
                ['POST', '/api/trips/start', 'Start a new trip', true],
                ['POST', '/api/trips/{id}/sync', 'Batch GPS points (max 100)', true],
                ['POST', '/api/trips/{id}/pause', 'Pause active trip', true],
                ['POST', '/api/trips/{id}/resume', 'Resume paused trip', true],
                ['POST', '/api/trips/{id}/end', 'End trip + run analytics', true],
                ['GET', '/api/trips/active', 'Get active trip', true],
                ['GET', '/api/trips', 'List trip history', true],
                ['GET', '/api/trips/{id}', 'Trip details + GPS points', true],
                ['POST', '/api/trips/offline-sync', 'Sync offline-recorded trips', true],
                ['GET', '/api/trips/{id}/share', 'Generate share link', true],
                ['GET', '/api/trips/{id}/weather', 'Fetch trip weather', true],
                ['GET', '/api/trips/{id}/analysis', 'AI driving analysis', true],
                ['POST', '/api/trips/{id}/photos', 'Upload trip photo', true],
                ['POST', '/api/trips/{id}/telemetry', 'Sync OBD-II data', true],
            ],
            'Social' => [
                ['POST', '/api/users/{id}/follow', 'Follow driver', true],
                ['DELETE', '/api/users/{id}/follow', 'Unfollow driver', true],
                ['POST', '/api/trips/{id}/like', 'Like a trip', true],
                ['POST', '/api/trips/{id}/comments', 'Comment on trip', true],
            ],
            'Leaderboards & Routes' => [
                ['GET', '/api/leaderboards?category=safety&period=monthly&scope=global', 'Global leaderboard', true],
                ['GET', '/api/routes?filter=popular', 'Browse routes', true],
                ['GET', '/api/routes/{id}/leaderboard?category=fastest', 'Per-route rankings', true],
            ],
            'Challenges' => [
                ['GET', '/api/challenges', 'List challenges with progress', true],
                ['GET', '/api/challenges/summary', 'Active / completed / points / streak', true],
                ['GET', '/api/challenges/mine', 'My joined challenges', true],
                ['GET', '/api/challenges/{id}', 'Challenge detail + progress', true],
                ['POST', '/api/challenges/{id}/join', 'Join a challenge', true],
            ],
            'Community Reports' => [
                ['GET', '/api/reports', 'List active road reports (bbox filter)', true],
                ['POST', '/api/reports', 'Submit report (optional photo)', true],
                ['POST', '/api/reports/{id}/verify', 'Upvote / verify report', true],
                ['POST', '/api/reports/{id}/dispute', 'Downvote / dispute report', true],
            ],
            'Other' => [
                ['GET', '/api/dashboard', 'Home dashboard stats', true],
                ['GET', '/api/analytics', 'Driving analytics', true],
                ['GET', '/api/search?q=nairobi&type=all', 'Search drivers/routes/trips', true],
                ['GET', '/api/notifications', 'In-app notifications', true],
                ['POST', '/api/sos', 'Trigger emergency SOS', true],
                ['GET', '/api/share/{token}', 'Public shared trip', false],
            ],
        ];
    }

    private function openApiSpec(): array
    {
        return [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'PaceBoard API',
                'description' => 'Driving tracking, rankings, and community API for PaceBoard.',
                'version' => '1.0.0',
            ],
            'servers' => [['url' => config('app.url')]],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'Sanctum',
                    ],
                ],
            ],
            'security' => [['bearerAuth' => []]],
            'paths' => $this->buildPaths(),
        ];
    }

    private function buildPaths(): array
    {
        $paths = [];

        foreach ($this->endpoints() as $group => $items) {
            foreach ($items as [$method, $path, $summary, $auth]) {
                $openApiPath = preg_replace('/\{(\w+)\}/', '{$1}', $path);
                $operation = [
                    'summary' => $summary,
                    'tags' => [$group],
                    'responses' => ['200' => ['description' => 'Success']],
                ];
                if (! $auth) {
                    $operation['security'] = [];
                }
                $paths[$openApiPath][strtolower($method)] = $operation;
            }
        }

        return $paths;
    }
}
