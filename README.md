# Projects Panel

A tiny Laravel + Livewire app where people post projects as links.

- **All projects** (`/`) — every project from every user, newest first.
- **My projects** (`/my-projects`) — post your own project (title + link), or delete one.

## Stack

- Laravel 13, Livewire 4 with Volt single-file components, Tailwind CSS 4 (Vite)
- Alpine, which Livewire already bundles, for the small bits of local state (the delete confirm, the toast)
- [Motion](https://motion.dev) for the animations that fire once — things arriving, leaving, reacting
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
resources/views/layouts/app.blade.php              # sky, header, tabs, theme switch, toast
resources/views/components/                        # project-card, field, icon
resources/views/pagination.blade.php               # newer / older, passed to ->links()
```

## Design

Soft gradients, a lot of blur, and no hard edges — "dreamy" is the whole brief.

- **Colour.** A fixed layer of drifting blurred blobs sits behind everything (`.dream-sky`), pale
  lilac and mint by day, a violet night sky with stars after dark. Surfaces on top of it are frosted
  glass rather than solid fills, and no neutral is pure grey — they are all pulled towards violet.
- **Type.** [Fraunces](https://fonts.google.com/specimen/Fraunces) for display, with its `SOFT` axis
  turned up so the serifs are rounded rather than sharp, and [Quicksand](https://fonts.google.com/specimen/Quicksand)
  for everything else. Both are self-hosted via `@fontsource-variable` and bundled by Vite, so there
  is no webfont fetch at build time or run time.
- **Motion.** Rows cascade in when a page renders; a project you just posted unfolds from nothing and
  pushes the list down; deleting one slides it out and closes the gap before the server is even told.
  The perpetual background drift is CSS keyframes, not Motion — it should not cost a main-thread
  frame every 16ms for as long as the tab is open. Everything is off under `prefers-reduced-motion`.

The markup reaches Motion through a single Alpine magic:

```html
<li wire:key="project-7" x-data x-init="$dream.enter($el)">
```

`resources/js/app.js` decides what that means. A whole batch of elements arriving in one frame is a
page render, so they cascade; a single one arriving after the page has settled was just created, so
it unfolds. Livewire's morph *moves* rows when a keyed list is reordered, and a moved element is torn
out and re-inserted, so `x-init` runs again on rows that are not new — `wire:key` values that have
already had their entrance are remembered and skipped.

## Run it with Docker

The app joins the external `edge` network, which the cloudflared stack creates. Bring cloudflared up
first, or create the network yourself:

```bash
docker network create edge   # only if the cloudflared stack is not up
docker compose up -d --build
```

Served on port 3000. The hostname `project-panel` comes from the container name, so add it to your
hosts file if you want to reach it directly:

```bash
echo '127.0.0.1 project-panel' | sudo tee -a /etc/hosts
```

Open <http://project-panel:3000> and register an account.

### Behind the Cloudflare tunnel

Point the tunnel's public hostname at **`http://project-panel:3000`** — cloudflared resolves that name
over the shared `edge` network, so no ports need publishing at all (drop the `ports` block if the
tunnel should be the only way in).

Cloudflare terminates TLS and cloudflared forwards plain HTTP, so the app trusts the forwarded headers
(`trustProxies` in `bootstrap/app.php`). Without that, Laravel would generate `http://` asset and form
URLs on an `https://` page and the browser would block them. Set `APP_URL` to the public tunnel URL as
well; it is used for links generated outside a request.

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

Email verification is planned, so `users.email_verified_at` is carried in the schema even though
nothing writes it — turning the feature on later should be a feature, not a data migration. It needs
`Notifiable` back on the `User` model to send the notification.
