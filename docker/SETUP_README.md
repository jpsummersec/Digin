# Project setup — Docker development environment

This file describes how to set up and run the project's Docker environment and common troubleshooting tips.

**Files of interest**
- [docker-compose.yaml](docker-compose.yaml)
- [nginx.conf](nginx.conf)
- [PHP.dockerfile](PHP.dockerfile)
- [nginx.dockerfile](nginx.dockerfile)

**Prerequisites**
- Docker Engine and Docker Compose v2 installed.
- Clone the repository into your working folder.

Quick steps

1. (Optional) Create an `.env` file in project root with DB credentials, for example:

```env
DB_ROOT_PASSWORD=your_strong_password
DB_ROOT_USER=root
DB_SERVER=mysql
```

2. Build and start the full environment:

```bash
docker compose -f docker-compose.yaml -f docker-compose.spotify.yml up -d --build
```

3. Verify containers are running:

```bash
docker compose -f docker-compose.yaml -f docker-compose.spotify.yml ps --all
```

Useful commands

- View last 200 lines of combined logs:

```bash
docker compose -f docker-compose.yaml -f docker-compose.spotify.yml logs --tail=200
```

- Follow logs live for `web` and `php`:

```bash
docker compose -f docker-compose.yaml -f docker-compose.spotify.yml logs -f web php
```

- Stop and remove containers:

```bash
docker compose -f docker-compose.yaml -f docker-compose.spotify.yml down
```

- Rebuild a single service (e.g., `php`):

```bash
docker compose -f docker-compose.yaml -f docker-compose.spotify.yml build php
docker compose -f docker-compose.yaml -f docker-compose.spotify.yml up -d php
```

Common issues & fixes

- Problem: `nginx` fails to start with `host not found in upstream "php"`.
  - Cause: `nginx` is configured to proxy to service name `php`, but `php` service either wasn't built as php-fpm or is not running.
  - Fix:
    - Ensure `PHP.dockerfile` uses a PHP-FPM image (it should be `FROM php:fpm`). See [PHP.dockerfile](PHP.dockerfile).
    - Build & start services: `docker compose -f docker-compose.yaml -f docker-compose.spotify.yml up -d --build`.
    - Check `php` container status and logs: `docker compose logs --tail=100 php`

- Problem: `php` container immediately exits or runs an interactive CLI.
  - Cause: Using a non-FPM image or overriding the command.
  - Fix: Use the `php:fpm` base image and make sure the `docker-compose.yaml` uses the built image (or build from `PHP.dockerfile`).

Tips

- Keep the local `app/` folder synced via the volume declared in [docker-compose.yaml](docker-compose.yaml).
- If ports conflict, check `ps` output to see bound ports and free them or change the mapping in `docker-compose.yaml`.
