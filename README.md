# Projects Panel

A tiny Laravel + Livewire app where people post projects as links.

- **All projects** (`/`) — every project from every user, newest first.
- **My projects** (`/my-projects`) — post your own project (title + link), or delete one.

## Stack

- Laravel 13, Livewire 4 with Volt single-file components, Tailwind CSS 4 (Vite)
- **SQLite** — chosen for RAM: it is an in-process library with no server daemon, so it costs a few MB
  of page cache instead of the ~100MB+ resident set a MariaDB server holds just to idle. Nothing here
  needs concurrent writers, so there is no reason to pay for one.
- Light / dark / auto theme, stored in `localStorage`, defaulting to the OS preference.

## Data model

`users` ──< `projects` (`user_id`, `title`, `url`). A user has many projects; a project belongs to a user.

## Layout

Each page is one Volt file — markup and component class together:

```
resources/views/livewire/projects.blade.php        # tab 1: everyone's projects
resources/views/livewire/my-projects.blade.php     # tab 2: post / delete your own
resources/views/livewire/auth/login.blade.php
resources/views/livewire/auth/register.blade.php
resources/views/layouts/app.blade.php              # tabs, theme switch, log in / out
```

## Run it with Docker

Served on port 3000. The hostname `project-panel` comes from the container name, so add it to your
hosts file once:

```bash
echo '127.0.0.1 project-panel' | sudo tee -a /etc/hosts
```

Then:

```bash
docker compose up -d --build
```

Open <http://project-panel:3000> and register an account.

The SQLite file lives in the `database` volume, so data survives `docker compose down`. Migrations run
on container start. An `APP_KEY` is generated inside the container if you do not supply one; set it in
the environment to keep sessions valid across rebuilds:

```bash
APP_KEY=$(docker compose run --rm --no-deps app php artisan key:generate --show) docker compose up -d
```

## Run it locally instead

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
npm run dev        # and, in another shell:
php artisan serve
```

## Tests

```bash
php artisan test
```

## Deliberately left out (YAGNI)

Email verification, password reset, login throttling, editing projects, and project descriptions.
Auth is email + password only.
