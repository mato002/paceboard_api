# PaceBoard API

Laravel backend for the **PaceBoard** driver community app — trips, hazards, leaderboards, SOS, challenges, and admin console.

## Requirements

- PHP 8.2+
- Composer
- MySQL 8+ (or SQLite for local tests)
- Node.js (optional — admin uses CDN assets)

## Quick start (local)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan serve
```

Admin console: `http://localhost:8000/admin/login`  
API docs: `http://localhost:8000/api/docs`

### Demo accounts

| Role | Email | Password |
|------|-------|----------|
| Admin | `test@example.com` | `password` |
| Driver | `james.kariuki@paceboard.test` | `password` |

Seed demo drivers, nearby trips, and popular routes:

```bash
php artisan db:seed --class=DriversAndRoutesSeeder
```

## Production deploy

1. Push to `main` — GitHub Actions FTP-deploys to your server.
2. Set production `.env` (see `.env.production.example`).
3. Migrations run automatically on the next web request when `PACEBOARD_AUTO_MIGRATE=true`, or trigger manually:

```bash
php artisan db:seed --class=DriversAndRoutesSeeder   # optional demo data
# or visit: /setup/migrate?token=YOUR_SETUP_TOKEN
```

4. **Run a queue worker** (required for push notifications):

```bash
php artisan queue:work --tries=3
```

5. **Schedule cron** (shared hosting cPanel):

```
* * * * * cd /path/to/paceboard && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks: prune expired hazard reports (hourly), recalculate leaderboards (daily 00:15).

## Health check

```
GET /health
GET /up          (Laravel default)
```

Returns database, storage, and cache status — use for uptime monitoring.

## Key environment variables

| Variable | Purpose |
|----------|---------|
| `APP_URL` | Public API URL |
| `DEPLOY_HOOK_TOKEN` | GitHub deploy hook (optional) |
| `SETUP_TOKEN` | Manual `/setup/migrate` trigger |
| `PACEBOARD_AUTO_MIGRATE` | Auto-run migrations on web requests |
| `FCM_ENABLED` + `FCM_SERVER_KEY` | Push notifications |
| `QUEUE_CONNECTION` | Use `database` + worker in production |
| `OPENWEATHER_API_KEY` | Live weather on dashboard |
| `CORS_ALLOWED_ORIGINS` | Allowed web origins |

## API overview

Authenticated routes use `Authorization: Bearer {token}` (Laravel Sanctum).

| Area | Endpoints |
|------|-----------|
| Auth | `POST /api/login`, `POST /api/register` |
| Trips | `POST /api/trips/start`, sync, end |
| Hazards | `GET/POST /api/reports`, `/api/reports/nearby` |
| Drivers nearby | `GET /api/drivers/nearby?lat=&lng=` |
| Routes | `GET /api/routes?filter=popular&limit=8` |
| Leaderboards | `GET /api/leaderboards`, `/api/leaderboards/winners` |
| Dashboard | `GET /api/dashboard?lat=&lng=` |
| Vehicles | `GET/POST /api/vehicles` (includes trip stats) |
| SOS | `POST /api/sos` |

Full reference: `/api/docs`

## Admin console

Web UI at `/admin/*` for users, trips, road alerts (hazards), SOS, challenges, routes, vehicles, leaderboards, settings, and broadcasts.

## Tests

```bash
php artisan test
```

CI runs on every push to `main`.

## Flutter app

Pair with the **paceboard-app** Flutter project. Set the API base URL in `dart_defines.json` to match `APP_URL`.

## License

Proprietary — PaceBoard Technologies.
