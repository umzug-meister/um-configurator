# um-configurator

WordPress-Plugin "Konfigurator" — Admin-Panel für Umzugsartikel, Dienstleistungen und Aufträge.

## Build

```sh
npm run build   # gulp zip → zips src/ into dist/um-configurator.zip
```

No test framework, no linter, no typecheck.

## Version

Stored in two places, both updated automatically by semantic-release on push to `main`:

- `src/um-configurator.php:14` — `* Version:      X.Y.Z`
- `package.json:3` — `"version": "X.Y.Z"`

Git tag is created and `dist/um-configurator.zip` is uploaded to the GitHub release.

## Architecture

- **Entry point**: `src/um-configurator.php` — plugin bootstrap, registers CPTs, taxonomies, REST routes, hooks.
- **REST API**: `um-configurator/v1` — endpoints in `src/inc/*-endpoints.php`. GET routes are public; POST/PUT/DELETE require `is_user_logged_in()` except `POST /order/` (customer order submission).
- **Email**: `src/app-dist/messages/templates/` — `email.php`, `subject_company.php`, `subject_customer.php`. Two emails sent per order (company + customer).
- **Settings**: Under WordPress Settings → Konfigurator. 3 fields: company email, from name, from address.
- **Theme stripping**: Output buffering + `simple_html_dom` removes header/footer/adminbar on the configurator page.
- **Auth**: `UM_CONFIG_DO_AUTH` = `'local' !== wp_get_environment_type()` — disabled in `local` env.

## SPA frontend

The JS frontend is a **separate project**. Its build output must be placed in `src/app-dist/konfigurator/` (currently absent from source — that dir doesn't exist). The plugin dynamically globs for `main.*.js`, `runtime.*.js`, `main.*.css` there and enqueues them.

## Local dev

```sh
docker compose up   # WordPress 6.2.2 on :8080, MariaDB 10.11
```

## Commits

Must follow conventional commits: `fix:`, `feat:`, `BREAKING CHANGE:`, `chore:`, `docs:`, `refactor:`, `test:`. Only commits matching these prefixes trigger a release.

## Release

Pushes to `main` with conventional commits (`fix:`, `feat:`, `BREAKING CHANGE:`) trigger a release:

1. Version bumped in PHP header and `package.json`
2. `npm run build` runs
3. Bump committed, tag created
4. `dist/um-configurator.zip` attached to GitHub release
