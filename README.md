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

## Contact messages

The portfolio's contact form posts to `POST /api/contact` (email, subject, message; limited to 5 a
minute per IP). Messages are stored here and shown at `/messages` to the account whose email is
`ADMIN_EMAIL`, and each one is emailed to that address through Gmail SMTP. Set these in `.env`
next to `docker-compose.yml`:

```bash
ADMIN_EMAIL=you@gmail.com
MAIL_MAILER=smtp
MAIL_USERNAME=you@gmail.com
MAIL_PASSWORD=your-16-char-app-password   # Google account > Security > App passwords
```

Leave `MAIL_MAILER` unset and the email is written to the container log instead (`docker logs project-panel`).
Register the admin account before setting `ADMIN_EMAIL`, since registration is open.

Publicly hosted at https://chickensplash.dpdns.org