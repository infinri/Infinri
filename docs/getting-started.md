# Getting Started

Welcome to **Infinri**!  This guide walks you through setting up a local development environment in the shortest possible time.

---

## Prerequisites

* PHP 8.4+ with required extensions (pdo_pgsql, intl, mbstring, etc.)
* PostgreSQL 16
* Redis 7 (optional for first-run)
* Node.js 18+ **and** [Bun](https://bun.sh/) (or NPM/Yarn)
* [RoadRunner 3](https://roadrunner.dev/) binary (`rr`)

> You can also use Docker for Postgres/Redis; see `docker-compose.yml`.

---

## TL;DR (60 seconds)

```bash
# 1 — Clone & install PHP deps
composer install

# 2 — Copy environment & tweak DB creds
cp .env.example .env

# 3 — Run DB migrations
php app/cli.php migrations:migrate

# 4 — Start RoadRunner (port 8080)
./rr serve -d
```

Open `http://localhost:8080`—you should see the landing page.

Default admin credentials: **admin@example.com / changeme**.

---

## Detailed Steps

### 1. Database

Create a Postgres database and user, e.g.

```sql
CREATE DATABASE infinri;
CREATE USER infinri WITH PASSWORD 'secret';
GRANT ALL PRIVILEGES ON DATABASE infinri TO infinri;
```

Update `.env` with your connection settings.

### 2. Install Front-end Assets

```bash
bun install     # or npm install / yarn install
bun run dev     # watches + rebuilds assets
```

### 3. Running Tests

```bash
vendor/bin/phpunit   # full suite
```

### 4. Code Quality

```bash
composer analyse   # PHPStan
composer cs-fix    # PHP-CS-Fixer
```

---

Happy hacking!  If you run into issues, open an issue or join the discussion board.
