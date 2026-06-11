# Hosting Behind Nginx / PHP-FPM

This starter installs cleanly from Packagist, but serving the created app
through Nginx and PHP-FPM adds one host boundary that `composer create-project`
cannot safely automate: the CLI user that created the app may differ from the
PHP-FPM user that handles browser requests.

Use this guide for self-managed development hosts. Production platforms such as
Laravel Cloud, Forge, containers, or your own deployment tooling may use a
different ownership policy; keep the same principle, but adapt the commands to
your host.

## First Hosted Check

After creating and building the app:

```bash
composer create-project xuple/evolayer-base-starter my-app
cd my-app
npm install
npm run build
php artisan evolayer:doctor --strict --no-ansi
```

Point Nginx at the Laravel public directory:

```text
root /path/to/my-app/public;
```

Set `APP_URL` to the URL you serve through Nginx:

```env
APP_URL=https://app.example.test
```

Then verify both CLI and browser-facing paths:

```bash
php artisan evolayer:doctor --strict --no-ansi
curl -kI https://app.example.test
```

## PHP-FPM Write Permissions

Laravel needs the web server process to write framework-generated files under
`storage` and `bootstrap/cache`. This starter uses SQLite for the first-hour
install, so `database/database.sqlite` must also be writable by the PHP-FPM
process when `DB_CONNECTION=sqlite`.

Find the PHP-FPM user/group on your host:

```bash
ps -eo user,group,cmd | grep '[p]hp-fpm'
```

Inspect the writable paths:

```bash
ls -ld storage storage/framework storage/framework/views bootstrap/cache database
ls -l database/database.sqlite
```

A common development-host pattern is to keep the files owned by your shell user
while granting the PHP-FPM group write access. Replace `www-data` with the group
used by your PHP-FPM pool:

```bash
sudo chown -R "$USER":www-data storage bootstrap/cache database
sudo chmod -R ug+rwX storage bootstrap/cache database
sudo find storage bootstrap/cache database -type d -exec chmod g+s {} \;
php artisan optimize:clear
```

Do not put those commands in Composer scripts. Ownership is host policy, and the
right group differs across distributions, PHP-FPM pools, containers, and managed
platforms.

## `tempnam()` During Browser Requests

If the browser returns a 500 and the logs include:

```text
tempnam(): file created in the system's temporary directory
```

the likely cause is that PHP-FPM cannot write the intended Laravel compiled view
or cache path. Re-check group ownership and writability for:

```text
storage/
storage/framework/
storage/framework/views/
bootstrap/cache/
database/database.sqlite
```

Run `php artisan optimize:clear` after correcting ownership so Laravel drops any
stale compiled files.

## Created App Lockfile

The starter repository intentionally does not commit `composer.lock`, so every
`composer create-project` install resolves the current public package graph.

A created client application is different: commit the generated
`composer.lock` so deploys use the dependency graph you tested.

Generated or environment-specific files should remain untracked:

```text
.env
vendor/
node_modules/
public/build/
bootstrap/ssr/
```

## Vite Behind Nginx

For a direct browser workflow, Vite's default behavior is convenient: if the
requested port is busy, it tries the next available port. Behind Nginx, that is
brittle because the reverse proxy keeps pointing at the original port.

When Nginx proxies to a fixed Vite port, set the same port in `.env` so the
starter enables `strictPort` and a collision fails loudly:

```env
APP_URL=https://app.example.test
VITE_DEV_SERVER_PORT=5186
VITE_DEV_SERVER_ORIGIN=
```

Then start Vite normally:

```bash
npm run dev
```

When `VITE_DEV_SERVER_PORT` is empty, Vite keeps its normal flexible dev-server
behavior and may move to the next available port. When it is set, Vite binds to
`127.0.0.1:<port>` and fails instead of falling forward if that port is already
occupied. Host-level Nginx configuration remains outside the starter.

If the browser should load Vite assets through the same origin as the Laravel
app instead of directly from `127.0.0.1:<port>`, set
`VITE_DEV_SERVER_ORIGIN` to the app URL:

```env
APP_URL=https://app.example.test
VITE_DEV_SERVER_PORT=5186
VITE_DEV_SERVER_ORIGIN=https://app.example.test
```

Origin mode still binds Vite to `127.0.0.1:<port>`, but it makes Laravel's hot
file point at the configured origin and tells Vite's HMR client to use
`/vite-hmr` on that origin. Your Nginx vhost must proxy `/@vite/`,
`/@react-refresh`, `/resources/`, and `/vite-hmr` to the same local Vite port.
`VITE_DEV_SERVER_ORIGIN` must be an `http://` or `https://` origin only, with no
path, query, fragment, or credentials, and it requires `VITE_DEV_SERVER_PORT`.

This starter's generic example assumes Nginx and Vite run on the same machine,
with Nginx proxying browser requests to Vite over loopback. If a downstream app
serves a LAN or dev-domain URL with a different topology, configure that app's
host/HMR/origin policy locally instead of copying client-specific domains into
the starter.

For agent handoff, do not leave `npm run dev` running after a one-off check
unless the user asks for a live browser session. If you intentionally leave it
running, report the working directory, configured port, and process/session. To
check the expected port, use:

```bash
ss -ltnp | grep ':5186 '
```

To restart, stop the existing Vite process, confirm `.env` and Nginx still use
the same port, then run `npm run dev` again. A 502 on `/@vite/`,
`/@react-refresh`, `/resources/`, or `/vite-hmr` usually means Vite is stopped,
bound to a different port, or the host proxy was not updated/reloaded.

If you host several local apps, find a free port before updating the Nginx
example:

```bash
comm -23 \
  <(seq 5174 5199 | sort -n) \
  <(
    {
      sudo nginx -T 2>/dev/null | grep -Eo '127\.0\.0\.1:[0-9]+' | cut -d: -f2
      ss -ltnH | awk '{print $4}' | sed -nE 's/.*:([0-9]+)$/\1/p'
    } | sort -n | uniq
  ) | head -10
```

Then use the same port in both places:

```env
VITE_DEV_SERVER_PORT=5186
VITE_DEV_SERVER_ORIGIN=https://app.example.test
```

```nginx
proxy_pass http://127.0.0.1:5186;
```

See [`nginx-dev-vhost.example.conf`](nginx-dev-vhost.example.conf) for a generic
starting point.
