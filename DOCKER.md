# Docker Development

This setup runs the backend stack in Docker:

- `server`: Nginx HTTP server
- `app`: PHP 8.4 FPM Laravel runtime managed by Supervisor
- `postgres`: PostgreSQL database
- `redis`: Redis queue backend

Supervisor starts these processes inside the `app` container:

- `php-fpm`
- `php artisan horizon`

## Requirements

- Docker Desktop

## Start

```powershell
docker compose up -d --build --remove-orphans
```

Run the first-time Laravel setup inside Docker:

```powershell
docker compose exec app composer install
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate
```

The API is available at:

```txt
http://127.0.0.1:8000
```

Redis is exposed to Windows on:

```txt
127.0.0.1:6379
```

Postgres is exposed to Windows on:

```txt
127.0.0.1:5433
```

Inside Docker, Laravel connects to:

```txt
postgres:5432
redis:6379
```

## Logs

View all app process logs, including PHP-FPM and the queue worker:

```powershell
docker compose logs -f app
```

Horizon is available at:

```txt
http://127.0.0.1:8000/horizon
```

View Nginx logs:

```powershell
docker compose logs -f server
```

## Useful Commands

```powershell
docker compose exec app php artisan config:clear
docker compose exec app php artisan migrate:fresh --seed
docker compose exec redis redis-cli ping
docker compose logs -f server app
docker compose down
```

To remove database, Redis, and Composer dependencies stored in Docker volumes:

```powershell
docker compose down -v
```
