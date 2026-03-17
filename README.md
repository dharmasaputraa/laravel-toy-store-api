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
- Clean architecture (Service + DTO + Resource)

---

# Code Structure

This project follows a layered architecture to improve maintainability, scalability, and testability.

## Directory Overview

```
app/
├── DTOs/
│   └── Health/
│       └── HealthData.php
│
├── Services/
│   └── HealthCheckService.php
│
├── Http/
│   ├── Controllers/
│   │   ├── Api/
│   │   │   └── V1/
│   │   │       └── HealthController.php
│   │   │
│   │   └── Web/
│   │       └── (optional controllers)
│   │
│   ├── Resources/
│   │   └── V1/
│   │       └── HealthResource.php
│   │
│   └── Middleware/
│
├── Traits/
│   └── ApiResponse.php
```

---

## Architecture Layers

### 1. Service Layer

Located in:

```
app/Services
```

Responsibilities:

- Business logic
- External service checks (DB, Redis, Storage)
- No HTTP or response formatting

Example:

```php
HealthCheckService::full()
```

---

### 2. DTO (Data Transfer Object)

Located in:

```
app/DTOs
```

Responsibilities:

- Standardize data structure between layers
- Provide typed data objects
- Decouple service logic from controllers

Example:

```php
HealthData::make(...)
```

---

### 3. API Resources

Located in:

```
app/Http/Resources
```

Responsibilities:

- Transform data into API responses
- Control output format
- Enable API versioning

Example:

```php
return new HealthResource($data);
```

---

### 4. Controllers

Located in:

```
app/Http/Controllers/Api/V1
```

Responsibilities:

- Handle HTTP requests
- Call services
- Return resources

Controllers are intentionally kept thin.

---

### 5. Traits (Shared Utilities)

Located in:

```
app/Traits
```

Example:

- `ApiResponse` for consistent response structure

---

## Request Flow

```
Client Request
      ↓
Controller
      ↓
Service
      ↓
DTO
      ↓
Resource
      ↓
JSON Response
```

---

## API Versioning

API versioning is implemented at the directory level:

```
app/Http/Controllers/Api/V1
app/Http/Resources/V1
```

Future versions:

```
V2/
V3/
```

This allows:

- Non-breaking changes
- Independent evolution of API versions
- Backward compatibility

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

---

### 5. Run Frontend Dev Server

```bash
composer run dev
```

---

### 6. Serve Laravel

```bash
php artisan serve
```

Application:

```
http://localhost:8000
```

---

## Logging (Pail)

```bash
docker logs -f laravel_pail
```

---

## Queue Processing (Horizon)

```
http://localhost:8000/horizon
```

---

## Storage (RustFS)

```
http://127.0.0.1:9000
```

---

## Health Check Endpoints

### Basic

```
GET /api/health
```

### Full

```
GET /api/health/full
```

Includes:

- Database
- Redis
- Object Storage (RustFS)
- Response time

---

## Docker Configuration

### Base (`docker-compose.yml`)

- PostgreSQL
- Redis
- RustFS
- Mailpit

### Development (`docker-compose.override.yml`)

- Horizon
- Pail

### Production (`docker-compose.prod.yml`)

- App (PHP-FPM)
- Nginx
- Horizon

---

## Production Deployment

### 1. Use Production Environment

```bash
cp .env.prod .env
```

---

### 2. Run Containers

```bash
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

---

### 3. Migrate

```bash
docker exec -it laravel_app php artisan migrate --force
```

---

### 4. Optimize

```bash
docker exec -it laravel_app php artisan optimize
```

---

## Important Notes

- Application runs on host during development for faster iteration
- Infrastructure runs entirely in Docker
- Horizon and Pail mirror production behavior
- Redis is required for queue processing
- RustFS replaces traditional S3

---

## Common Issues

### Redis Connection Refused

```bash
docker ps
```

---

### Horizon Issues

```bash
docker logs laravel_horizon
```

---

### Storage Issues

Check:

- `AWS_ENDPOINT`
- `AWS_USE_PATH_STYLE_ENDPOINT`
- RustFS container health

---

## License

MIT

---
