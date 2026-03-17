# Laravel API Boilerplate

A production-ready Laravel API boilerplate with a hybrid development setup:

- Application runs on host (Windows)
- Infrastructure runs in Docker
- Queue processing via Horizon
- Log monitoring via Pail
- Object storage via RustFS (S3-compatible)

---

## Architecture Overview

### Development Environment

| Component   | Runtime        |
| ----------- | -------------- |
| Laravel App | Host (Windows) |
| Vite        | Host (Windows) |
| Horizon     | Docker         |
| Pail        | Docker         |
| PostgreSQL  | Docker         |
| Redis       | Docker         |
| RustFS (S3) | Docker         |
| Mailpit     | Docker         |

### Production Environment

| Component   | Runtime |
| ----------- | ------- |
| Laravel App | Docker  |
| Nginx       | Docker  |
| Horizon     | Docker  |
| PostgreSQL  | Docker  |
| Redis       | Docker  |
| RustFS (S3) | Docker  |

---

## Features

- Laravel 12
- Redis queue with Horizon
- Centralized log streaming via Pail
- Health check endpoints (`/health`, `/health/full`)
- S3-compatible storage (RustFS)
- Docker-based infrastructure
- Production-ready containerization

---

## Prerequisites

- PHP 8.2+
- Composer
- Node.js + NPM
- Docker & Docker Compose

---

## Installation (Development)

### 1. Clone Repository

```bash
git clone <your-repo>
cd <your-project>
```

---

### 2. Install Dependencies

```bash
composer install
npm install
```

---

### 3. Environment Setup

Copy environment file:

```bash
cp .env.example .env
```

Ensure important values:

```env
APP_ENV=local

DB_HOST=127.0.0.1
REDIS_HOST=127.0.0.1

QUEUE_CONNECTION=redis

FILESYSTEM_DISK=s3
AWS_ENDPOINT=http://127.0.0.1:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
```

---

### 4. Start Infrastructure

```bash
docker compose up -d
```

This will start:

- PostgreSQL
- Redis
- RustFS
- Mailpit
- Horizon
- Pail

---

### 5. Run Application

```bash
composer run dev
```

This runs:

- Vite dev server

---

### 6. Serve Laravel (Manual)

```bash
php artisan serve
```

Application will be available at:

```
http://localhost:8000
```

---

## Logging (Pail)

Pail runs inside Docker.

View logs:

```bash
docker logs -f laravel_pail
```

---

## Queue Processing (Horizon)

Horizon runs inside Docker.

Access dashboard:

```
http://localhost:8000/horizon
```

---

## Storage (RustFS)

S3-compatible storage is provided by RustFS.

Default endpoint (development):

```
http://127.0.0.1:9000
```

---

## Health Check Endpoints

### Basic Health Check

```
GET /api/health
```

Checks:

- Database
- Redis

---

### Full Health Check

```
GET /api/health/full
```

Checks:

- Database
- Redis
- Storage (RustFS)
- Response time

---

## Docker Configuration

### Base (`docker-compose.yml`)

Contains:

- PostgreSQL
- Redis
- RustFS
- Mailpit

---

### Development Override (`docker-compose.override.yml`)

Adds:

- Horizon
- Pail

---

### Production (`docker-compose.prod.yml`)

Adds:

- App container (PHP-FPM)
- Nginx
- Horizon

---

## Production Deployment

### 1. Use Production Environment

```bash
cp .env.prod .env
```

---

### 2. Build & Run Containers

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

---

### 3. Run Migrations

```bash
docker exec -it laravel_app php artisan migrate --force
```

---

### 4. Optimize Application

```bash
docker exec -it laravel_app php artisan optimize
```

---

## Important Notes

- Laravel application is intentionally not containerized in development for faster iteration.
- Horizon and Pail are containerized to maintain production parity.
- Redis is required for queue processing in all environments.
- RustFS replaces traditional S3 for both development and production.

---

## Common Issues

### Redis Connection Refused

Ensure:

```bash
docker ps
```

Redis container must be running.

---

### Horizon Not Processing Jobs

Check:

```bash
docker logs laravel_horizon
```

---

### Storage Errors

Verify:

- `AWS_ENDPOINT`
- `AWS_USE_PATH_STYLE_ENDPOINT=true`
- RustFS container is healthy

---

## License

MIT

---
