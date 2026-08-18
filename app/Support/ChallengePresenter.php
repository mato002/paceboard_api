<?php

namespace App\Support;

use App\Models\Achievement;
use App\Models\Challenge;
use App\Models\ChallengeParticipant;
use App\Models\User;

class ChallengePresenter
{
    public static function format(Challenge $challenge, ?User $user = null, ?ChallengeParticipant $participant = null): array
    {
        $expired = $challenge->ends_at !== null && $challenge->ends_at->isPast();
        $joined = $participant !== null;
        $completed = (bool) ($participant?->completed);
        $progress = (float) ($participant?->progress ?? 0);
        $target = max(1, (float) $challenge->target_value);
        $remaining = max(0, $target - $progress);
        $percent = (int) round(min(100, ($progress / $target) * 100));

        $status = match (true) {
            $completed => 'completed',
            $expired => 'expired',
            $joined && $progress > 0 => 'in_progress',
            $joined => 'joined',
            default => 'available',
        };

        $meta = self::metaFor($challenge);

        return [
            'id' => $challenge->id,
            'title' => $challenge->title,
            'description' => $challenge->description,
            'type' => $challenge->type,
            'category' => $meta['category'],
            'category_label' => $meta['category_label'],
            'target_value' => (float) $challenge->target_value,
            'target_label' => $meta['target_label'],
            'unit' => $meta['unit'],
            'reward_points' => (int) $challenge->reward_points,
            'starts_at' => $challenge->starts_at?->toIso8601String(),
            'ends_at' => $challenge->ends_at?->toIso8601String(),
            'ends_in_label' => self::endsInLabel($challenge),
            'participants_count' => (int) ($challenge->participants_count ?? $challenge->participants()->count()),
            'joined' => $joined,
            'completed' => $completed,
            'expired' => $expired,
            'status' => $status,
            'status_label' => self::statusLabel($status),
            'progress' => $progress,
            'remaining' => $remaining,
            'progress_percent' => $completed ? 100 : $percent,
            'progress_label' => self::progressLabel($challenge, $progress, $target, $completed),
            'remaining_label' => self::remainingLabel($challenge, $remaining, $completed),
            'requirements' => $meta['requirements'],
            'badge' => $meta['badge'],
            'completed_at' => $participant?->completed_at?->toIso8601String(),
        ];
    }

    public static function summary(User $user): array
    {
        $rows = ChallengeParticipant::query()
            ->with('challenge:id,reward_points')
            ->where('user_id', $user->id)
            ->get();

        $completed = $rows->where('completed', true);

        return [
            'active' => $rows->where('completed', false)->count(),
            'completed' => $completed->count(),
            'points' => (int) $completed->sum(fn ($row) => (int) ($row->challenge?->reward_points ?? 0)),
            'streak_days' => self::streakDays($user),
        ];
    }

    public static function badgeForType(string $type): ?array
    {
        $slug = match ($type) {
            'route' => 'route_explorer',
            'distance' => 'long_hauler',
            'weekend' => 'weekend_warrior',
            'safety' => 'safe_driver',
            'community' => 'road_guardian',
            'trips', 'night_drive' => 'pace_setter',
            default => null,
        };

        if (! $slug) {
            return null;
        }

        $achievement = Achievement::query()->where('slug', $slug)->first();
        if (! $achievement) {
            return ['slug' => $slug, 'name' => self::badgeName($slug), 'icon' => self::badgeIcon($slug), 'description' => null];
        }

        return [
            'slug' => $achievement->slug,
            'name' => $achievement->name,
            'icon' => $achievement->icon,
            'description' => $achievement->description,
        ];
    }

    private static function metaFor(Challenge $challenge): array
    {
        $target = (float) $challenge->target_value;
        $whole = rtrim(rtrim(number_format($target, 1, '.', ''), '0'), '.');

        return match ($challenge->type) {
            'route' => [
                'category' => 'routes',
                'category_label' => 'Route',
                'unit' => 'trips',
                'target_label' => $target <= 1 ? 'Complete this route once' : "Complete this route {$whole} times",
                'requirements' => [
                    'Start within the designated route',
                    'Complete the route in one recorded trip',
                    'Trip must be recorded by PaceBoard',
                ],
                'badge' => self::badgeForType('route'),
            ],
            'distance' => [
                'category' => 'distance',
                'category_label' => 'Distance',
                'unit' => 'km',
                'target_label' => "Drive {$whole} km",
                'requirements' => [
                    'Record trips with PaceBoard',
                    "Accumulate {$whole} km during the challenge window",
                ],
                'badge' => self::badgeForType('distance'),
            ],
            'trips' => [
                'category' => 'distance',
                'category_label' => 'Distance',
                'unit' => 'trips',
                'target_label' => $target <= 1 ? 'Complete 1 trip' : "Complete {$whole} trips",
                'requirements' => [
                    'Start and finish each trip in PaceBoard',
                    "Complete {$whole} trips before the challenge ends",
                ],
                'badge' => self::badgeForType('trips'),
            ],
            'weekend' => [
                'category' => 'competitive',
                'category_label' => 'Weekend',
                'unit' => 'trips',
                'target_label' => $target <= 1 ? 'Complete 1 weekend trip' : "Complete {$whole} weekend trips",
                'requirements' => [
                    'Trips must start on Saturday or Sunday',
                    'Trip must be recorded by PaceBoard',
                ],
                'badge' => self::badgeForType('weekend'),
            ],
            'night_drive' => [
                'category' => 'competitive',
                'category_label' => 'Competitive',
                'unit' => 'trips',
                'target_label' => $target <= 1 ? 'Complete 1 night trip' : "Complete {$whole} night trips",
                'requirements' => [
                    'Start between 10 PM and 5 AM',
                    'Trip must be recorded by PaceBoard',
                ],
                'badge' => self::badgeForType('night_drive'),
            ],
            'safety' => [
                'category' => 'safety',
                'category_label' => 'Safety',
                'unit' => 'trips',
                'target_label' => $target <= 1 ? 'Complete 1 safe trip' : "Complete {$whole} safe trips",
                'requirements' => [
                    'Finish the trip with a high safety score',
                    'Avoid harsh braking and speeding events',
                ],
                'badge' => self::badgeForType('safety'),
            ],
            'community' => [
                'category' => 'community',
                'category_label' => 'Community',
                'unit' => 'reports',
                'target_label' => $target <= 1 ? 'Report 1 hazard' : "Report {$whole} hazards",
                'requirements' => [
                    'Submit accurate road reports',
                    'Help other drivers with timely alerts',
                ],
                'badge' => self::badgeForType('community'),
            ],
            default => [
                'category' => 'competitive',
                'category_label' => 'Challenge',
                'unit' => 'pts',
                'target_label' => "Reach {$whole}",
                'requirements' => ['Record activity in PaceBoard to earn progress'],
                'badge' => self::badgeForType($challenge->type),
            ],
        };
    }

    private static function progressLabel(Challenge $challenge, float $progress, float $target, bool $completed): string
    {
        if ($completed) {
            return 'Completed';
        }

        $unit = self::metaFor($challenge)['unit'];
        $current = rtrim(rtrim(number_format($progress, 1, '.', ''), '0'), '.');
        $goal = rtrim(rtrim(number_format($target, 1, '.', ''), '0'), '.');

        return match ($unit) {
            'km' => "{$current} / {$goal} km",
            'trips' => "{$current} / {$goal} trips",
            'reports' => "{$current} / {$goal} reports",
            default => "{$current} / {$goal}",
        };
    }

    private static function remainingLabel(Challenge $challenge, float $remaining, bool $completed): ?string
    {
        if ($completed) {
            return null;
        }

        $unit = self::metaFor($challenge)['unit'];
        $value = rtrim(rtrim(number_format($remaining, 1, '.', ''), '0'), '.');

        return match ($unit) {
            'km' => "{$value} km remaining",
            'trips' => $remaining <= 1 ? '1 trip remaining' : "{$value} trips remaining",
            'reports' => $remaining <= 1 ? '1 report remaining' : "{$value} reports remaining",
            default => "{$value} remaining",
        };
    }

    private static function endsInLabel(Challenge $challenge): ?string
    {
        if (! $challenge->ends_at) {
            return 'Ongoing';
        }

        $remaining = now()->diff($challenge->ends_at, false);
        if ($remaining->invert) {
            return 'Ended';
        }
        if ($remaining->days >= 1) {
            return $remaining->days.'d left';
        }
        if ($remaining->h >= 1) {
            return $remaining->h.'h left';
        }

        return max(1, $remaining->i).'m left';
    }

    private static function statusLabel(string $status): string
    {
        return match ($status) {
            'completed' => 'Completed',
            'expired' => 'Expired',
            'in_progress' => 'In progress',
            'joined' => 'Joined',
            default => 'Available',
        };
    }

    private static function streakDays(User $user): int
    {
        $dates = $user->trips()
            ->whereNotNull('ended_at')
            ->orderByDesc('ended_at')
            ->limit(90)
            ->pluck('ended_at')
            ->map(fn ($date) => $date?->toDateString())
            ->filter()
            ->unique()
            ->values();

        $streak = 0;
        $cursor = now()->startOfDay();
        foreach ($dates as $date) {
            if ($date !== $cursor->toDateString()) {
                break;
            }
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    private static function badgeName(string $slug): string
    {
        return match ($slug) {
            'route_explorer' => 'Route Explorer',
            'long_hauler' => 'Long Hauler',
            'weekend_warrior' => 'Weekend Warrior',
            'safe_driver' => 'Safe Driver',
            'road_guardian' => 'Road Guardian',
            'pace_setter' => 'Pace Setter',
            default => ucfirst(str_replace('_', ' ', $slug)),
        };
    }

    private static function badgeIcon(string $slug): string
    {
        return match ($slug) {
            'route_explorer' => '🗺️',
            'long_hauler' => '🛣️',
            'weekend_warrior' => '🌙',
            'safe_driver' => '🛡️',
            'road_guardian' => '🚨',
            'pace_setter' => '🏆',
            default => '🏅',
        };
    }
}
