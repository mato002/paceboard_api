<?php

namespace Database\Seeders;

use App\Models\Leaderboard;
use App\Models\Role;
use App\Models\Route;
use App\Models\Trip;
use App\Models\TripPoint;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DriversAndRoutesSeeder extends Seeder
{
    /** Nairobi CBD — nearby drivers are placed within ~10 km of this point. */
    private const CENTER_LAT = -1.2921;

    private const CENTER_LNG = 36.8219;

    public function run(): void
    {
        $driverRole = Role::firstOrCreate(
            ['slug' => 'driver'],
            ['name' => 'Driver', 'permissions' => ['drive', 'view_leaderboards']]
        );

        $popularRoutes = $this->seedPopularRoutes();
        $drivers = $this->seedDrivers($driverRole);

        foreach ($drivers as $index => $driver) {
            $vehicle = $this->seedVehicle($driver, $index);
            $this->seedCompletedTrips($driver, $vehicle, $popularRoutes, $index);

            if ($index < 6) {
                $this->seedActiveTrip($driver, $vehicle, $index);
            }
        }

        $this->seedLeaderboards($drivers);
    }

    /**
     * @return list<Route>
     */
    private function seedPopularRoutes(): array
    {
        $definitions = [
            ['name' => 'Nairobi CBD → Westlands', 'start_city' => 'Nairobi', 'end_city' => 'Westlands', 'total_trips' => 148],
            ['name' => 'Nairobi → Nakuru Highway', 'start_city' => 'Nairobi', 'end_city' => 'Nakuru', 'total_trips' => 96],
            ['name' => 'Thika Road Express', 'start_city' => 'Nairobi', 'end_city' => 'Thika', 'total_trips' => 82],
            ['name' => 'Westlands → JKIA', 'start_city' => 'Westlands', 'end_city' => 'Embakasi', 'total_trips' => 74],
            ['name' => 'Karen → Nairobi CBD', 'start_city' => 'Karen', 'end_city' => 'Nairobi', 'total_trips' => 61],
            ['name' => 'Ngong Road → Runda', 'start_city' => 'Ngong', 'end_city' => 'Runda', 'total_trips' => 53],
        ];

        $routes = [];

        foreach ($definitions as $definition) {
            $route = Route::firstOrCreate(
                [
                    'start_city' => $definition['start_city'],
                    'end_city' => $definition['end_city'],
                ],
                [
                    'name' => $definition['name'],
                    'total_trips' => $definition['total_trips'],
                    'is_popular' => true,
                ]
            );

            $route->update([
                'name' => $definition['name'],
                'total_trips' => max($route->total_trips, $definition['total_trips']),
                'is_popular' => true,
            ]);

            $routes[] = $route->fresh();
        }

        return $routes;
    }

    /**
     * @return list<User>
     */
    private function seedDrivers(Role $driverRole): array
    {
        $definitions = [
            [
                'name' => 'James Kariuki',
                'email' => 'james.kariuki@paceboard.test',
                'phone' => '+254711000101',
                'county' => 'Nairobi',
                'bio' => 'Weekend cruiser. Loves Thika Road runs.',
                'total_distance' => 842.5,
                'driving_hours' => 68.2,
                'reward_points' => 1250,
            ],
            [
                'name' => 'Amina Hassan',
                'email' => 'amina.hassan@paceboard.test',
                'phone' => '+254711000102',
                'county' => 'Nairobi',
                'bio' => 'Daily commuter between Westlands and CBD.',
                'total_distance' => 612.0,
                'driving_hours' => 51.4,
                'reward_points' => 980,
            ],
            [
                'name' => 'Brian Ochieng',
                'email' => 'brian.ochieng@paceboard.test',
                'phone' => '+254711000103',
                'county' => 'Kiambu',
                'bio' => 'Safety-first driver. Top weekly score holder.',
                'total_distance' => 1104.8,
                'driving_hours' => 92.0,
                'reward_points' => 1680,
            ],
            [
                'name' => 'Faith Wanjiru',
                'email' => 'faith.wanjiru@paceboard.test',
                'phone' => '+254711000104',
                'county' => 'Nairobi',
                'bio' => 'Exploring popular routes around Nairobi.',
                'total_distance' => 455.3,
                'driving_hours' => 38.6,
                'reward_points' => 720,
            ],
            [
                'name' => 'David Mutua',
                'email' => 'david.mutua@paceboard.test',
                'phone' => '+254711000105',
                'county' => 'Machakos',
                'bio' => 'Mombasa Road regular. Early morning driver.',
                'total_distance' => 978.1,
                'driving_hours' => 79.5,
                'reward_points' => 1410,
            ],
            [
                'name' => 'Grace Akinyi',
                'email' => 'grace.akinyi@paceboard.test',
                'phone' => '+254711000106',
                'county' => 'Nairobi',
                'bio' => 'Community reporter and city explorer.',
                'total_distance' => 388.7,
                'driving_hours' => 33.1,
                'reward_points' => 640,
            ],
        ];

        $drivers = [];

        foreach ($definitions as $definition) {
            $driver = User::firstOrCreate(
                ['email' => $definition['email']],
                [
                    ...$definition,
                    'password' => Hash::make('password'),
                    'country' => 'Kenya',
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                    'privacy_accepted_at' => now(),
                    'driver_status' => 'verified',
                    'profile_visibility' => 'public',
                ]
            );

            if (! $driver->roles()->where('slug', 'driver')->exists()) {
                $driver->roles()->attach($driverRole);
            }

            $drivers[] = $driver;
        }

        return $drivers;
    }

    private function seedVehicle(User $driver, int $index): Vehicle
    {
        $vehicles = [
            ['manufacturer' => 'Toyota', 'model' => 'Corolla', 'year' => 2019, 'color' => 'Silver', 'registration_number' => 'KDA 101A', 'fuel_type' => 'petrol'],
            ['manufacturer' => 'Mazda', 'model' => 'CX-5', 'year' => 2021, 'color' => 'Red', 'registration_number' => 'KDB 202B', 'fuel_type' => 'petrol'],
            ['manufacturer' => 'Subaru', 'model' => 'Forester', 'year' => 2020, 'color' => 'Blue', 'registration_number' => 'KDC 303C', 'fuel_type' => 'petrol'],
            ['manufacturer' => 'Honda', 'model' => 'Fit', 'year' => 2018, 'color' => 'White', 'registration_number' => 'KDD 404D', 'fuel_type' => 'petrol'],
            ['manufacturer' => 'Nissan', 'model' => 'X-Trail', 'year' => 2022, 'color' => 'Black', 'registration_number' => 'KDE 505E', 'fuel_type' => 'diesel'],
            ['manufacturer' => 'Volkswagen', 'model' => 'Polo', 'year' => 2017, 'color' => 'Grey', 'registration_number' => 'KDF 606F', 'fuel_type' => 'petrol'],
        ];

        $vehicleData = $vehicles[$index % count($vehicles)];

        return Vehicle::firstOrCreate(
            [
                'user_id' => $driver->id,
                'registration_number' => $vehicleData['registration_number'],
            ],
            [
                ...$vehicleData,
                'mileage' => 45000 + ($index * 8200),
                'last_service_odometer_km' => 40000 + ($index * 8000),
                'last_service_at' => now()->subMonths(2),
                'service_interval_km' => 10000,
            ]
        );
    }

    /**
     * @param  list<Route>  $routes
     */
    private function seedCompletedTrips(User $driver, Vehicle $vehicle, array $routes, int $index): void
    {
        if ($driver->trips()->whereNotNull('ended_at')->exists()) {
            return;
        }

        $completions = [
            ['route' => 0, 'name' => 'Morning commute', 'distance' => 14.2, 'score' => 94, 'hours_ago' => 30],
            ['route' => 1, 'name' => 'Nakuru weekend run', 'distance' => 158.0, 'score' => 88, 'hours_ago' => 72],
            ['route' => 2, 'name' => 'Thika evening drive', 'distance' => 42.5, 'score' => 91, 'hours_ago' => 120],
        ];

        foreach ($completions as $tripIndex => $completion) {
            $route = $routes[$completion['route'] % count($routes)];
            $startedAt = now()->subHours($completion['hours_ago'] + $tripIndex);
            $durationSeconds = (int) ($completion['distance'] * 90);

            Trip::create([
                'user_id' => $driver->id,
                'vehicle_id' => $vehicle->id,
                'route_id' => $route->id,
                'name' => $completion['name'],
                'start_location' => $route->start_city,
                'start_city' => $route->start_city,
                'destination' => $route->end_city,
                'end_city' => $route->end_city,
                'start_lat' => self::CENTER_LAT + ($index * 0.004),
                'start_lng' => self::CENTER_LNG + ($index * 0.003),
                'end_lat' => self::CENTER_LAT + 0.02,
                'end_lng' => self::CENTER_LNG + 0.015,
                'distance' => $completion['distance'],
                'duration_seconds' => $durationSeconds,
                'average_speed' => 45 + $tripIndex,
                'top_speed' => 72 + ($tripIndex * 3),
                'moving_time_seconds' => (int) ($durationSeconds * 0.85),
                'stopped_time_seconds' => (int) ($durationSeconds * 0.15),
                'score' => $completion['score'],
                'visibility' => 'public',
                'started_at' => $startedAt,
                'ended_at' => $startedAt->copy()->addSeconds($durationSeconds),
            ]);
        }
    }

    private function seedActiveTrip(User $driver, Vehicle $vehicle, int $index): void
    {
        if ($driver->trips()->whereNull('ended_at')->exists()) {
            return;
        }

        $nearbyPoints = [
            ['lat' => -1.2864, 'lng' => 36.8172, 'location' => 'Upper Hill'],
            ['lat' => -1.3012, 'lng' => 36.8078, 'location' => 'Kilimani'],
            ['lat' => -1.2789, 'lng' => 36.8345, 'location' => 'Parklands'],
            ['lat' => -1.2650, 'lng' => 36.8020, 'location' => 'Industrial Area'],
            ['lat' => -1.3100, 'lng' => 36.8280, 'location' => 'Ngong Road'],
            ['lat' => -1.2835, 'lng' => 36.8255, 'location' => 'CBD'],
        ];

        $point = $nearbyPoints[$index % count($nearbyPoints)];

        $trip = Trip::create([
            'user_id' => $driver->id,
            'vehicle_id' => $vehicle->id,
            'name' => 'Live drive near '.$point['location'],
            'start_location' => $point['location'],
            'start_city' => 'Nairobi',
            'destination' => 'On route',
            'start_lat' => $point['lat'],
            'start_lng' => $point['lng'],
            'distance' => 3.5 + $index,
            'duration_seconds' => 900 + ($index * 120),
            'average_speed' => 28 + $index,
            'top_speed' => 55 + $index,
            'moving_time_seconds' => 780,
            'stopped_time_seconds' => 120,
            'score' => 90,
            'visibility' => 'public',
            'started_at' => now()->subMinutes(20 + ($index * 5)),
            'ended_at' => null,
        ]);

        TripPoint::create([
            'trip_id' => $trip->id,
            'latitude' => $point['lat'],
            'longitude' => $point['lng'],
            'heading' => 45 + ($index * 30),
            'accuracy' => 8.5,
            'speed' => 32 + $index,
            'recorded_at' => now()->subMinutes(2),
        ]);
    }

    /**
     * @param  list<User>  $drivers
     */
    private function seedLeaderboards(array $drivers): void
    {
        foreach (['weekly', 'monthly'] as $period) {
            foreach (['score', 'distance', 'safety'] as $categoryIndex => $category) {
                foreach ($drivers as $rank => $driver) {
                    Leaderboard::firstOrCreate(
                        [
                            'user_id' => $driver->id,
                            'category' => $category,
                            'period' => $period,
                        ],
                        [
                            'rank_position' => $rank + 1,
                            'score_value' => match ($category) {
                                'distance' => $driver->total_distance,
                                'safety' => 95 - $rank,
                                default => 92 - $rank,
                            },
                        ]
                    );
                }
            }
        }
    }
}
