# Project setup - Docker development environment

This file describes how to set up and run the project's Docker environment and common troubleshooting tips.

**Files of interest**
- [docker-compose.yaml](docker-compose.yaml)
- [database/init.sql](database/init.sql)
- [nginx.conf](nginx.conf)
- [PHP.dockerfile](PHP.dockerfile)
- [nginx.dockerfile](nginx.dockerfile)

**Prerequisites**
- Docker Engine and Docker Compose v2 installed.
- Clone the repository into your working folder.

## Quick steps

1. Go to the Docker folder:

```bash
cd docker
```

2. Check or update `.env` with the database credentials:

```env
DB_ROOT_PASSWORD=your_strong_password
DB_ROOT_USER=root
DB_SERVER=mysql
```

3. Build and start the full environment:

```bash
docker compose up -d --build
```

4. Verify containers are running:

```bash
docker compose ps --all
```

5. Open the app and database tools:

- App: http://localhost
- phpMyAdmin: http://localhost:8080
- Caddy endpoint: http://localhost:3000

## Database startup import

The editable starter database file is [database/init.sql](database/init.sql).

MariaDB automatically imports this file only when the `mysqldata` Docker volume is created for the first time. Later `docker compose up` runs keep the existing database data and do not re-import this file.

To recreate the database from `database/init.sql`, intentionally remove the Docker volume:

```bash
docker compose down -v
docker compose up -d --build
```

Only use `docker compose down -v` when you want to delete the local database data.

## Useful commands

View last 200 lines of combined logs:

```bash
docker compose logs --tail=200
```

Follow logs live for `web` and `php`:

```bash
docker compose logs -f web php
```

Stop and remove containers without deleting database data:

```bash
docker compose down
```

Rebuild a single service, for example `php`:

```bash
docker compose build php
docker compose up -d php
```

## Common issues and fixes

Problem: `nginx` fails to start with `host not found in upstream "php"`.

Cause: `nginx` is configured to proxy to service name `php`, but the `php` service either was not built as PHP-FPM or is not running.

Fix:

```bash
docker compose up -d --build
docker compose logs --tail=100 php
```

Problem: `php` container immediately exits or runs an interactive CLI.

Cause: The PHP image is not using PHP-FPM or the service command was overridden.

Fix: Use the `php:fpm` base image in [PHP.dockerfile](PHP.dockerfile) and make sure [docker-compose.yaml](docker-compose.yaml) uses the built image.

Problem: changes to [database/init.sql](database/init.sql) do not appear in phpMyAdmin.

Cause: MariaDB only runs files from `/docker-entrypoint-initdb.d` when the database volume is first created.

Fix: For a fresh local database, run:

```bash
docker compose down -v
docker compose up -d --build
```

## Tips

- Keep the local `app/` folder synced via the volume declared in [docker-compose.yaml](docker-compose.yaml).
- If ports conflict, check `docker compose ps --all` to see bound ports and free them or change the mapping in [docker-compose.yaml](docker-compose.yaml).
