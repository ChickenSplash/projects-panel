# Projects Panel

A tiny Laravel + Livewire app where people post projects as links.

- **All projects** (`/`) — every project from every user, newest first.
- **My projects** (`/my-projects`) — post your own project (title + link), or delete one.
- **Profile** (`/profile`) — change your username, email or password, or delete your account. Reached
  from the account menu in the header, which is the username itself once you are logged in.
- **API token** (`/api-token`) — generate a token and post projects from outside the app. Reached from
  the same account menu.

Usernames are unique: the column carries a unique index and registering with one that is already
spoken for comes back as "Username already taken".

## Stack

- Laravel 13, Livewire 4 with Volt single-file components, Tailwind CSS 4 (Vite)
- Laravel Sanctum for the one API endpoint, bearer tokens only
- Laravel MCP and Laravel Passport for the Claude connector — OAuth, because a custom
  connector will not take a static token
- Alpine, which Livewire already bundles, for the small bits of local state (the delete confirm, the toast)
- [Motion](https://motion.dev) for the animations that fire once — things arriving, leaving, reacting
- **SQLite** — chosen for RAM: it is an in-process library with no server daemon, so it costs a few MB
  of page cache instead of the ~100MB+ resident set a MariaDB server holds just to idle. Nothing here
  needs concurrent writers, so there is no reason to pay for one.
- Light / dark / auto theme, stored in `localStorage`, defaulting to the OS preference.

## Data model

`users` ──< `projects` (`user_id`, `title`, `url`). A user has many projects; a project belongs to a user.

Sanctum's `personal_access_tokens` hangs off `users` too, though only through a polymorphic
`tokenable`, so there is no foreign key and nothing cascades — deleting an account sweeps its tokens
by hand.

Passport adds `oauth_clients`, `oauth_auth_codes`, `oauth_access_tokens` and `oauth_refresh_tokens`
for the connector. The device code grant is switched off in `OAuthServiceProvider`, so its table is
not carried.

## API

One endpoint, so a project can be posted from a script instead of the form. Generate a token on
`/api-token` (in the account menu) and send it as a bearer token:

```bash
curl -X POST https://yourapp.test/api/projects \
  -H "Authorization: Bearer <token>" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"title": "My Project", "link": "https://example.com"}'
```

```json
{"id": 7, "title": "My Project", "link": "https://example.com", "created_at": "2026-08-13T17:49:13.000000Z"}
```

`201` on success, `422` with the usual Laravel error bag if the title or link is wrong, `401` without a
usable token. The project belongs to the token's owner and is indistinguishable from one posted through
the form — same `create` call, same validation rules.

The field is `link` rather than the `url` the column is called, matching the label on the form.

`sanctum.guard` is set to `[]`, so a bearer token is the *only* way in. Left at its default of `['web']`
Sanctum would also accept a browser session, and since API routes carry no CSRF token any other site
could then post a project on a signed-in user's behalf.

## MCP connector

The same thing again for Claude: an MCP server at **`POST /mcp/projects`**, offering three tools over
the shelf of whoever authorised the connection.

| Tool | Takes | Does |
|---|---|---|
| `create-project` | `title`, `link` | Posts a project, through the same `create` call as the form and the API — so one arrives owned and identical whichever of the three posted it |
| `list-projects` | `limit` (optional, 1–100, 25 by default) | Reads the shelf back newest first, with the id each other tool needs. Carries `readOnlyHint` |
| `delete-project` | `id` | Removes one, scoped the same way the delete button is. Carries `destructiveHint`, so a client can ask before it happens |

There is no edit: the app has none either, so the way to correct a project is to delete it and post it
again. Everything is scoped to the account that authorised — an id belonging to someone else does not
match, and is turned down in exactly the same words as an id that never existed, so the refusal cannot
be used to go fishing for what other people have posted.

`list-projects` answers with structured content against a declared `outputSchema`, and reports the
whole shelf's `total` alongside the `showing` it returned, so a model that asked for ten of forty can
tell there are thirty more rather than assuming it has seen everything.

Claude will not hold a static token for a custom connector, so this endpoint is behind OAuth 2.1
(Passport) rather than Sanctum. The two live side by side and neither opens the other's door — an
API token is refused at `/mcp/projects` and an OAuth token is refused at `/api/projects`.

```
POST /mcp/projects                                  the server, behind auth:api
GET  /.well-known/oauth-authorization-server        who issues tokens, and how
GET  /.well-known/oauth-protected-resource/…        which authorization server guards this resource
POST /oauth/register                                clients registering themselves
GET  /oauth/authorize                               the consent screen
POST /oauth/token                                   code for token
```

Connecting it, in Claude → Settings → Connectors → Add custom connector, is just the URL:
`https://<your-domain>/mcp/projects`. Nothing needs pasting into **Advanced settings**, because
`/oauth/register` lets Claude register itself and pick up its own client ID. Clients registered that
way hold no secret, so PKCE (`S256`) is required rather than optional — a request without a challenge
is a 400, not a fallback.

Approving it lands on this app's own consent screen (`resources/views/oauth/authorize.blade.php`,
named in `OAuthServiceProvider` — Passport ships no screen and binds no default, so `/oauth/authorize`
is a container error until one is). Then Claude has a token and the tool appears in its list.

Three things about being reachable, all of which look like the app is broken when they are wrong:

- **Anthropic connects from their own infrastructure, not from your device.** `localhost` and `.test`
  will not do; it has to be a public HTTPS URL. The Cloudflare tunnel below is enough.
- **Set `APP_URL` to that public URL.** The discovery documents are absolute, and a connector stuck
  at "Disconnected" is usually them pointing somewhere Anthropic cannot reach.
- **Do not put Cloudflare Access in front of `/mcp` or `/oauth`.** It would challenge Anthropic's
  requests, which have no browser to answer with.

The endpoint is rate limited to 60 requests a minute, which the `/api/projects` route is not — that
one needs a token you generated by hand, while this one is on the open internet answering strangers
before it knows who they are.

To poke at the tool without Claude in the loop:

```bash
php artisan mcp:inspector mcp/projects
```

## Layout

Each page is one Volt file — markup and component class together:

```
resources/views/livewire/projects.blade.php        # tab 1: everyone's projects
resources/views/livewire/my-projects.blade.php     # tab 2: post / delete your own
resources/views/livewire/profile.blade.php         # details, password, delete account
resources/views/livewire/api-token.blade.php       # generate / revoke the API token
resources/views/livewire/auth/login.blade.php
resources/views/livewire/auth/register.blade.php
resources/views/layouts/app.blade.php              # sky, header, account menu, tabs, theme switch, toast
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

Passport's token signing keys work the same way: the container makes an ephemeral pair if you do not
supply one, and every connector has to authorise again after a restart. To keep them, generate a pair
once and pass it in through `PASSPORT_PRIVATE_KEY` and `PASSPORT_PUBLIC_KEY` (an `.env` file beside
`docker-compose.yml` is the least painful place for multi-line values):

```bash
docker compose run --rm --no-deps app php artisan passport:keys
```

## Run it locally instead

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate
php artisan passport:keys    # signs the connector's OAuth tokens
npm run dev        # and, in another shell:
php artisan serve
```

## Tests

```bash
php artisan test
```

## Deliberately left out (YAGNI)

Email verification, password reset, login throttling, editing projects, and project descriptions.
Auth is email + password only. Changing your password on the profile page needs the current one, so it
is not a reset; forgetting it is still unrecoverable.

The API is one endpoint and one token. No listing, reading or deleting over HTTP, no token abilities,
no expiry, no naming your tokens, and no rate limiting — the `api` group ships without a throttle and
nothing here adds one, which matches the login page not being throttled either. A token is generated,
shown once, and replaced or revoked from the same page; anything more is a feature nobody has asked for
yet, and the UI is a placeholder besides.

The MCP server has no resources and no prompts, no editing (the app has none to expose), and no way
to read anyone else's shelf — the front page is public in a browser, but that is not the same as
handing a connector a feed of it. Its OAuth side is equally bare. There is a `mcp:use` scope
because Laravel MCP registers one, but nothing checks it — three tools acting as one user, all of them
already limited to that user's own shelf, leave nothing for a second permission to mean. A scope worth
having would be one that grants posting without granting deleting, and that is a decision to make when
somebody wants it. Token lifetimes are Passport's defaults, and there is no page listing
what you have connected or letting you disconnect it: revoking means deleting the row for now.

Email verification is planned, so `users.email_verified_at` is carried in the schema even though
nothing writes it — turning the feature on later should be a feature, not a data migration. It needs
`Notifiable` back on the `User` model to send the notification.
