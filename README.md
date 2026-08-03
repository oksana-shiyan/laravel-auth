# laravel-auth

Laravel API authentication example with Sanctum tokens, role-based permissions, and a Vue frontend.

## Features

- User registration and login with Laravel Sanctum personal access tokens
- Email verification and password reset
- Profile and password updates
- Token management (list, create, revoke) with role/permission checks via Spatie
- Admin endpoints to inspect and revoke tokens across users
- Roles: `user` (profile read/write, own-token management) and `admin` (all permissions; cross-user token admin)

API routes live under `/api/v1`.

## Requirements

- Docker and Docker Compose

## Run with Docker

1. Copy the environment file and adjust Docker-related values if needed:

```bash
cp .env.example .env
```

Set these for the Compose stack:

```env
APP_URL=http://localhost:81

DB_CONNECTION=pgsql
DB_HOST=database
DB_PORT=5432
DB_DATABASE=laravel_auth
DB_USERNAME=postgres
DB_PASSWORD=postgres

MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025

COMPOSE_NGINX_PORT=81
COMPOSE_DB_PORT=5435
CORS_SERVER_NAME=
```

2. Start the stack:

```bash
docker compose up -d
```

Services:

| Service   | Purpose              | Host port (default) |
|-----------|----------------------|---------------------|
| nginx     | HTTP entrypoint      | `81`                |
| php-fpm   | PHP / Laravel        | —                   |
| database  | PostgreSQL 13        | `5435`              |
| node      | Vite frontend (`npm run dev`) | `5173`     |
| mailhog   | Local SMTP / UI      | `1288` (SMTP), `8780` (UI) |

3. Install PHP dependencies and prepare the app (inside `php-fpm`):

```bash
docker compose exec php-fpm composer install
docker compose exec php-fpm php artisan key:generate
```

4. Create the database (first run only), then migrate and seed:

```bash
docker compose exec database psql -U postgres -c "CREATE DATABASE laravel_auth;"
docker compose exec php-fpm php artisan migrate --seed
```

5. Install frontend dependencies if needed (Node service uses the project volume):

```bash
docker compose run --rm node npm install
docker compose up -d node
```

App URL: [http://localhost:81](http://localhost:81)  
MailHog UI: [http://localhost:8780](http://localhost:8780)

### Seeded users

| Email              | Role  |
|--------------------|-------|
| `test@example.com` | user  |
| `admin@example.com`| admin |

Passwords come from the user factory default (`password`).

### Useful commands

```bash
# Artisan
docker compose exec php-fpm php artisan ...

# Tests
docker compose exec php-fpm ./vendor/bin/pest

# Stop
docker compose down
```
