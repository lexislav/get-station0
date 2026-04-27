# get-station0 — CLAUDE.md

## Project overview

This is the **project skeleton** (`lexislav/get-station0`, type: project) for the Station0 flat-file CMS.
Users install it via `composer create-project lexislav/get-station0 mysite`.

The CMS core library is `lexislav/station0` — installed into `vendor/` and updated independently via `composer update lexislav/station0`.

## Key directories

| Path | Purpose |
|---|---|
| `public/` | Web root — `index.php`, assets, uploads |
| `site/config.php` | App config factory (`fn($station0Root, $siteRoot, $projectRoot): array`) |
| `site/content/pages/` | Flat-file pages (`page.txt` per directory) |
| `site/templates/` | Twig templates + block definitions |
| `writable/` | Cache, sessions, logs, `db.sqlite` (gitignored at runtime) |

## Running locally

```bash
composer install
php -S localhost:8080 -t public public/index.php
```

On first visit to `http://localhost:8080/admin`, the setup form creates the first admin account.

## Local development against local library

To work against a local clone of `lexislav/station0` instead of Packagist:

```json
"repositories": [{"type": "path", "url": "../station0"}],
"require": {"lexislav/station0": "*@dev"}
```

Then `composer update`. **Do not commit this change** — revert before tagging a release.

## Content format

Pages live in `site/content/pages/{slug}/page.txt`:

```
Title: Page title
Template: page
Published: true
---
Markdown body
```

Or block-based (YAML list in body):

```
Title: Work
Template: blocks
Published: true
---

- type: text
  body: |
    # Heading
- type: gallery
  columns: 3
  images:
    - src: /uploads/photo.jpg
      alt: Photo
```

## Block schema format

Each block type lives in `site/templates/blocks/{type}/`:

- `schema.yaml` — field definitions for the admin editor
- `template.twig` — Twig template rendering the block

Fields are a dict keyed by name. Supported types: `text`, `textarea`, `image`, `number`, `select`, `boolean`, `list`.
The `image` type renders an upload button in the admin (accepts jpg, png, gif, webp, svg).

## Demo pages

| URL | Demonstrates |
|---|---|
| `/` | Plain Markdown homepage |
| `/about` | Markdown with links to sub-pages |
| `/about/team` | Nested URL (`/about/team`) |
| `/about/history` | Nested URL (`/about/history`) |
| `/work` | Block-based page: `text` + `gallery` blocks, `Template: blocks` |

## Environment variables

```
BASE_URL=http://localhost:8080
DEBUG=true
ADMIN_PATH=/admin
HTTPS=false
SITE_PATH=           # optional override for site/ location
MAIL_HOST=...
```

## Common gotchas

- `writable/` must be writable by the web server — created automatically by Bootstrap on first request.
- `composer update` updates the CMS core; your `site/` content is never touched.
- `php vendor/bin/console user:create <username> <email> [role]` creates users from CLI.
- Always start the dev server from the skeleton root — path resolution depends on `getcwd()`.
