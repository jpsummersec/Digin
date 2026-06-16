# DigIn

DigIn is a hilarious mobile-first cooking web application. It uses Spoonacular for recipe data and Spotify for optional music playback during cooking.

## Team Members (IT1D)

- Lorenzo
- JP
- Oleh
- Michael
- Klaudia
- Reka

## Prerequisites

- Docker Engine
- Docker Compose v2
- Spotify application credentials (+ request access from JP)
- At least one Spoonacular API key

## Setup

1. Confirm that the existing `docker/.env` file contains the required database values:

```env
DB_ROOT_PASSWORD=YOUR_PASSWORD_HERE
DB_ROOT_USER=root
DB_SERVER=mysql
```

2. Copy `docker/app/public/php/config-example.php` to `docker/app/public/php/config.php`.

3. Add the Spotify application credentials to `config.php`:

```php
<?php

require_once __DIR__ . '/include-url-config.php';

return [
    'SPOTIFY_CLIENT_ID' => 'YOUR_SPOTIFY_CLIENT_ID',
    'SPOTIFY_CLIENT_SECRET' => 'YOUR_SPOTIFY_CLIENT_SECRET',
    'SPOTIFY_REDIRECT_URI' => $baseUrl . '/php/callback.php'
];
```

4. Build and start the Docker environment:

```bash
cd docker
docker compose up -d --build
```

5. Verify that the containers are running:

```bash
docker compose ps --all
```

6. Add at least one Spoonacular API key to the `spoonacular_api_key` table through phpMyAdmin or SQL:

```sql
INSERT INTO spoonacular_api_key (api_key_value)
VALUES ('YOUR_SPOONACULAR_API_KEY');
```

7. Open the application and database tools:

- Application: [http://127.0.0.1:3000](http://127.0.0.1:3000)
- phpMyAdmin: [http://localhost:8080](http://localhost:8080)

## Project Configuration

The local application URL is configured in [`docker/app/public/php/include-url-config.php`](docker/app/public/php/include-url-config.php). The default URL is `http://127.0.0.1:3000`.

The Spotify application's registered redirect URL must match:

```text
http://127.0.0.1:3000/php/callback.php
```

The main Docker configuration files are:

- [`docker/docker-compose.yaml`](docker/docker-compose.yaml)
- [`docker/database/init.sql`](docker/database/init.sql)
- [`docker/nginx.conf`](docker/nginx.conf)
- [`docker/PHP.dockerfile`](docker/PHP.dockerfile)
- [`docker/nginx.dockerfile`](docker/nginx.dockerfile)

## Database Startup Import

MariaDB imports [`docker/database/init.sql`](docker/database/init.sql) only when the `mysqldata` Docker volume is created for the first time. Later `docker compose up` runs preserve the existing database and do not import the file again.

To recreate the database from `init.sql`, run the following commands from the `docker` directory:

```bash
docker compose down -v
docker compose up -d --build
```

Only use `docker compose down -v` when you intend to delete all local database data.

## Useful Commands

Run these commands from the `docker` directory.

View the last 200 lines of combined logs:

```bash
docker compose logs --tail=200
```

Follow the Nginx and PHP logs:

```bash
docker compose logs -f web php
```

Stop the containers without deleting database data:

```bash
docker compose down
```

Rebuild the PHP service:

```bash
docker compose build php
docker compose up -d php
```

## Common Issues

### Nginx Cannot Find PHP

If Nginx fails with `host not found in upstream "php"`, rebuild the environment and inspect the PHP logs:

```bash
docker compose up -d --build
docker compose logs --tail=100 php
```

### PHP Container Exits

If the PHP container exits immediately, verify that [`docker/PHP.dockerfile`](docker/PHP.dockerfile) uses the `php:fpm` base image and that [`docker/docker-compose.yaml`](docker/docker-compose.yaml) uses the built PHP image.

### Database Changes Are Not Imported

MariaDB imports `init.sql` only when its volume is first created. To apply the starter database again, recreate the volume:

```bash
docker compose down -v
docker compose up -d --build
```

### Port Conflicts

Use `docker compose ps --all` to inspect bound ports, then free conflicting ports before starting the environment again.

## Known Errors and Limitations

- Recipe search and recipe pages depend on Spoonacular. They fail when no API key is stored, the daily quota is exhausted, or the service is unavailable.
- Spotify playback requires valid Spotify application credentials and manual authentication from JP since we have not been approved to use the API freely, so users must be added to the development dashboard to use the feature.
- ADD MORE HERE
