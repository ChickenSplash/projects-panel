# Projects Panel

A tiny Laravel + Livewire app where people post projects as links, with a gratitude journal
alongside it.

## Stack

- Laravel 13, Livewire 4 (Volt single-file components)
- Tailwind CSS 4, built with Vite
- SQLite
- Laravel Sanctum for the one API endpoint

## Run it with Docker

The app joins the external `edge` network:

```bash
docker network create edge   # only if it does not exist yet
docker compose up -d --build
```

Served on port 3000. Migrations run on container start and the SQLite file lives in the `database`
volume, so data survives `docker compose down`.

An `APP_KEY` is generated inside the container if you do not supply one; set it in the environment
to keep sessions valid across rebuilds:

```bash
APP_KEY=$(docker compose run --rm --no-deps app php artisan key:generate --show) docker compose up -d
```

Open <http://localhost:3000> and register an account.
