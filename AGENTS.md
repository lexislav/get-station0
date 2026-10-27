# AGENTS.md — building a site with Station0

Guide for AI coding agents (Claude Code, Codex, Cursor, Copilot…) working in a
site made from **get-station0**. Humans: the same material, with more prose,
is in [README.md](README.md).

Station0 is a flat-file CMS on Slim 4 + Twig 3. Content is plain text in
`site/content/`, templates are Twig in `site/templates/`, the admin edits the
same files. SQLite (`writable/db.sqlite`) holds only user accounts.

## Ground rules

- **Never edit `vendor/`.** The CMS core is `vendor/lexislav/station0/`,
  updated with `composer update lexislav/station0`. Everything site-specific
  lives in `site/`, `public/assets/` and `.env`.
- **Check the installed version** before using a feature marked
  *(≥ x.y)*: `composer show lexislav/station0 | grep versions`. What changed
  per version: `vendor/lexislav/station0/CHANGELOG.md`.
- `writable/` is runtime state (cache, sessions, logs, db) — don't commit or
  hand-edit it.
- After editing content or templates **by hand**, run
  `php vendor/bin/console cache:clear` (saves in the admin clear it already).
- Run the dev server and the console **from the project root**:
  `php -S localhost:8080 -t public public/index.php`.
- Field and front-matter keys are case-insensitive and lower-cased on read.
  Use `snake_case` names for your own fields.

## Map

| Path | What |
|---|---|
| `site/config.php` | Config factory `fn($station0Root, $siteRoot, $projectRoot): array` — name, timezone, paths, `access`, `thumbs`, `tasks`, `plugins`, mail |
| `.env` | `BASE_URL`, `DEBUG`, `ADMIN_PATH`, `HTTPS`, `ADMIN_LOCALE` (`en`/`cs`), `TIMEZONE`, `MAIL_*`, `TASKS_*` |
| `site/content/pages/` | Page tree — one directory per page |
| `site/content/collections/` | Headless content (no URL) |
| `site/content/_groups.yaml` | Admin menu tabs: label, icon, roles, order |
| `site/templates/<name>.twig` | Page templates (`layout.twig` = base, never a page template) |
| `site/templates/<name>.blocks.yaml` | Optional manifest per page template: page fields, builder rules |
| `site/templates/blocks/<type>/` | Page-builder block types: `schema.yaml` + `template.twig` |
| `site/templates/404.twig`, `410.twig` | Error pages (410 = archived / expired) |
| `site/actions/*.php` | Own request handlers (forms) — `_*.php` ignored |
| `site/tasks/*.php` | Own scripts (imports, exports), runnable from console and admin — `_*.php` ignored |
| `public/assets/` | Site CSS / JS / static files |

The demo content explains itself — every page describes the feature it uses,
and `/guide` (a stream of `topic` pages) covers them in order. Live demos:
`/` (Markdown), `/guide/visibility/*` (every visibility state), `/work` (one
block of each type), `/shop` (page fields, `Group: Shop`), `/blog` (a stream
of `article` pages with categories), `/studio` (collections, `Group: Marketing`), `/members`
(`Access: members`, `login.twig`), `/contact` (the `contact` site action).
The `banners` collection feeds the bar in `layout.twig`; redirect rules are
in `site/content/_redirects.yaml`. When you change a feature's demo, update
its `/guide` topic too.

## Pages

A page is `site/content/pages/<slug>/<template>.txt` (the homepage is
`site/content/pages/<template>.txt`). The **file name is the template**;
`site/templates/<template>.twig` renders it. The directory tree is the URL tree.

```
Title: Our services            # required
Status: published              # draft | published | archived
PublishAt: 2026-06-01 09:00    # optional, scheduled until then (site timezone)
ExpireAt: 2026-09-30 23:59     # optional, 410 Gone from then on
PublishedAt: 2026-06-01        # optional, display / sort date
Listing: nav-hidden            # listed (default) | nav-hidden | unlisted
Cascade: false                 # optional: hidden page does NOT hide its subtree
Sort: 10                       # order among siblings
AllowedChildTemplates: article # makes it a stream (comma list)
Group: Shop                    # admin menu tab for this page + subtree
Access: members                # public | members (see Members-only access)
Metatitle: …                   # optional <title> override
Subtitle: any extra key        # → page.extra.subtitle
---
Body: Markdown, or a YAML list of blocks.
```

- Not live (draft, scheduled, expired, archived, or under a hidden parent) →
  404, or 410 for archived / expired. Signed-in editors see a preview with a
  bar. Full rules: `vendor/lexislav/station0/docs/visibility.md`.
- Multi-line values and lists are indented YAML under the key; a single-line
  `Key: value` is read verbatim (colons and quotes are safe).
- Block body on disk:
  ```yaml
  - type: text
    body: |
      # Heading
      Some **Markdown**.
  - type: gallery
    columns: 3
    images:
      - src: photo.jpg
        alt: Photo
  ```
  A plain Markdown body is treated as one `text` block.

## Templates

```twig
{% extends 'layout.twig' %}
{% block title %}{{ page.metatitle ?? page.title }} · {{ app.name }}{% endblock %}
{% block body %}
  <h1>{{ page.title }}</h1>
  {{ content|raw }}
{% endblock %}
```

Variables in a page template: `page` (`title`, `metatitle`, `urlPath`, `slug`,
`template`, `status`, `state`, `isLive`, `listing`, `publishedAt`,
`publishAt`, `expireAt`, `author`, `updated`, `sort`, `extra`), `content`
(rendered body HTML — print with `|raw`), `fields` (typed page fields),
`preview` (editor viewing a non-live page). Globals: `app` (`name`,
`baseUrl`, `adminPath`), `csrf`, `visitor`, `access`.

A new `site/templates/<name>.twig` appears in the admin's Template dropdown.

## Twig helpers

| Helper | Returns |
|---|---|
| `top_level_pages()` | Live first-level pages for the main menu (skips `nav-hidden`, `unlisted`) |
| `nav_pages('/path')` | Live children for a submenu (same skips) |
| `child_pages('/path', includeUnlisted = false)` | Live children, sorted (skips `unlisted`) — listings, streams |
| `page('/path')` | A live page or `null` |
| `page_fields(page)` | Typed page fields of any page (e.g. children in a listing) |
| `collection('name')` | Live items of a collection, sorted by `Sort`, then title |
| `collection_item('name', 'slug')` / `collection_item('name/slug')` | One live item or `null` |
| `render_collection_item(item)` | Item body as HTML |
| `src\|thumb(w, h = 0, fit = 'contain')` | Resized image URL (`fit: 'cover'` crops); never upscales |
| `src\|thumb_srcset([400, 800])` | `srcset` string |
| `action_state('name')` | Flash / state of a site action — read **once** per render |

Page helpers take an optional last argument `includeGated = true` to keep
members-only pages for anonymous visitors (teaser lists).

## Blocks (page builder)

A block type is a directory `site/templates/blocks/<type>/`:

```yaml
# schema.yaml
label: Quote
fields:
  text:   { type: textarea, label: Quote }
  author: { type: text, label: Author }
  photo:  { type: image, label: Photo }
```

```twig
{# template.twig — values under `block`; image fields arrive as /media/… URLs #}
<blockquote>{{ block.text|nl2br }}
  {% if block.photo %}<img src="{{ block.photo|thumb(120, 120, 'cover') }}" alt="">{% endif %}
  <cite>{{ block.author }}</cite></blockquote>
```

`text` is built in (Markdown). A new block directory shows up in the admin's
"+ Add block" bar immediately.

## Template manifest (`<template>.blocks.yaml`)

All keys optional:

```yaml
fields:            # page fields, edited above the builder, stored in front matter
  subtitle: { type: text, label: Subtitle }
  price:    { type: number, label: Price, step: 0.01 }
  hero:     { type: image, label: Hero image }
blocks: false      # no builder at all (form-style page)
allowedBlocks: [text, gallery]   # palette, in this order
defaultBlocks: [gallery]         # pre-inserted into NEW pages (must be allowed)
```

In the template: `{{ fields.subtitle }}`, `{{ fields.hero }}` (a `/media/…`
URL), `{% for f in fields.features %}`. For other pages:
`page_fields(child).price`. Field names must not be reserved page keys
(`title`, `template`, `status`, `sort`, `body`, `group`, `listing`, …) —
such fields are dropped.

## Field types

Used in block `schema.yaml`, manifest `fields`, collection `_collection.yaml`
and task `params`:

| Type | Value in Twig | Options |
|---|---|---|
| `text`, `textarea` | string | `default` |
| `number` | number | `min`, `max`, `step` (decimals allowed by default) |
| `boolean` | `true` / `false` | |
| `select` | stored value | `options` or `options_from` (below), `placeholder` |
| `color` | `#rrggbb` | |
| `image` | `/media/…` URL (stored: bare filename) | |
| `file` | `/media/…` URL | |
| `list` | array of rows | `item:` = sub-fields (an `image` sub-field enables multi-upload) |

Select options:

```yaml
options: [s, m, l]                    # or {s: Small} or [{value, label, group}]
options_from: collection:products     # value = item slug → collection_item('products', value)
options_from: collections:a,b         # editor picks across collections; value = "a/slug"
options_from: pages:/blog             # value = URL path → page(value)
template: article                     #   (pages: only these templates)
group_by: category                    # <optgroup> by a field
sort_by: -price                       # "-" = descending
option_label: "{title} ({price} €)"   # {title}, {slug}, {<field>}, {collection}
```

Several relations ("related articles") = a `list` whose item has one `select`.

## Collections (headless content)

```
site/content/collections/<name>/
  _collection.yaml          ← optional: label, fields, group, title
  <slug>/item.txt           ← same front-matter format as pages
  <slug>/bg.jpg             ← the item's files
```

```yaml
label: People
group: Team                       # optional admin tab
title: "{name} {surname}"         # optional computed title (≥ 0.10)
fields:
  name:    { type: text, label: Name }
  surname: { type: text, label: Surname }
```

- Twig: `collection('people')`, `item.title`, `item.slug`,
  `item.extra.<field>`, `render_collection_item(item)` for the body.
- Items have `Status` / `PublishAt` / `ExpireAt` like pages; only live items
  are returned.
- **Computed title** *(≥ 0.10)*: `title: {from: "{brand} – {model}", input: readonly | editable | hidden}`.
  Placeholders = fields, `{slug}`, `{collection}`; separators next to empty
  fields are dropped. The result is stored in `Title:` on save. After adding
  or changing a pattern: `php vendor/bin/console collections:retitle <name> --dry-run`, then without `--dry-run`.

## Media

- Files live **next to their page** (`site/content/pages/about/team.jpg`) or
  item. Content stores the **bare filename** (`team.jpg`), never a
  `/media/…` URL, so pages can move. Station0 resolves it to
  `/media/<page-path>/<file>` (homepage: `/media/~/<file>`; collections:
  `/media/_collections/<name>/<slug>/<file>`).
- Uploads: jpg, png, gif, webp, svg (8 MB); documents via `file` fields.
  `.txt` files can't be uploaded to pages (the `.txt` is the content file).
- Absolute URLs (`https://…`, `/…`) pass through unchanged.
- Use `|thumb` on every image you show at a fixed size. Markdown images are
  thumbnailed automatically.

## Streams and menu groups

- **Stream** = a page with `AllowedChildTemplates: article`; its template
  lists `child_pages(page.urlPath)`. New children may only use the listed
  templates.
- **Menu groups**: `Group: Shop` (page front matter, whole subtree) and
  `group: Shop` (`_collection.yaml`) share one admin tab. Optional
  `site/content/_groups.yaml`:
  ```yaml
  shop: { label: Shop, icon: "🛒", roles: [editor] }
  ```

## Members-only access *(≥ 0.9)*

Off by default. In `site/config.php`:

```php
'access' => [
    'mode'      => 'members',        // or 'public' + `Access: members` on a section
    'public'    => ['/', '/info/*'],
    'redirect'  => '/',
    'loginPath' => '/login',         // member sign-in routes; template site/templates/login.twig
],
```

Per page / section: `Access: members` or `Access: public` in front matter
(inherited by subpages). Templates get `visitor` (`authenticated`, `member`,
`staff`, `label`, `email`) and `access` (`loginPath`, `logoutPath`, …).
Sign-out is a POST with the `csrf` fields. Member accounts:
`php vendor/bin/console user:create <user> <email> member`.

## Site actions — handling forms *(≥ 0.9)*

`site/actions/<name>.php` returns `['path', 'methods', 'access', 'handler']`;
see `site/actions/_example.php`. Every POST form must include the CSRF
fields:

```twig
<form method="post" action="/contact/send">
  <input type="hidden" name="{{ csrf.nameKey }}" value="{{ csrf.name }}">
  <input type="hidden" name="{{ csrf.valueKey }}" value="{{ csrf.value }}">
  …
</form>
{% set st = action_state('contact') %}{% if st.sent ?? false %}Thanks!{% endif %}
```

The handler gets `ActionContext`: `input()`, `state()->flash()`,
`throttle()->hit(key, max, seconds)`, `mail()`, `pages()`, `collections()`,
`visitor()`, `redirect('/path')`, `back()`, `render()`, `json()`.

## Site tasks — scripts and hooks *(≥ 0.8)*

`site/tasks/<name>.php` returns `['label', 'roles', 'params', 'on', 'run']`;
see `site/tasks/_example.php` and README → *Site tasks & hooks*. Keep the
file's top level free of side effects (it is loaded for every admin page);
do the work in `run`. Save content through `$task->collections()->save()` /
`$task->pages()` so computed titles and the cache stay right.

```bash
php vendor/bin/console task:list
php vendor/bin/console task:run <name> --param=value --flag
```

Hooks: `'on' => ['page.saved:/blog', 'collection.item.*:products']`.

## Plugins *(≥ 0.10, experimental)*

Optional features are enabled in `site/config.php` under `"plugins"`: a short
name loads a plugin bundled with Station0, a class name one from a Composer
package; unlisted plugins are never loaded. Don't write a plugin for a single
site — site actions, tasks and templates cover that; plugins are for features
shared between sites. `php vendor/bin/console plugins:list` shows what is
enabled; failures are in `writable/logs/app.log` (`Plugin '…' skipped`).

Bundled: `"redirects" => []` *(≥ 0.10.1)* — automatic 301s on slug change /
move, rules in `site/content/_redirects.yaml` (exact, `{slug}` / `{path*}`
patterns, regex, 410), 404 log. Redirect old URLs with a rule there rather
than with a page or a site action. See
`vendor/lexislav/station0/docs/redirects.md`.

## Recipes

- **New page type:** `site/templates/<t>.twig` (+ optional `<t>.blocks.yaml`
  with `fields`), then create the page in the admin or as
  `site/content/pages/<slug>/<t>.txt`.
- **Blog / news:** a parent page with `AllowedChildTemplates: article`, a
  `blog.twig` listing `child_pages(page.urlPath)`, an `article.twig`.
- **Reusable snippets / banners:** a collection + `collection()` in
  `layout.twig` or a block.
- **Pick a related item in a block:** a `select` with
  `options_from: collection:<name>`, then `collection_item('<name>', block.field)`.
- **Contact form:** a site action + a form with `csrf` fields +
  `action_state()`.
- **Gate a section:** `Access: members` on the section's root page and
  `access.loginPath` in config.

## Verify your work

1. `php vendor/bin/console cache:clear`
2. Start the server (above) and request the pages you touched — expect 200,
   or the 404 / 410 you intended.
3. Open the admin (`/admin`) and open the edit form of an affected page or
   item: the fields / blocks you declared must appear. A YAML syntax error in
   a schema or manifest can make the fields disappear without an error —
   re-check the indentation.
4. Errors: `writable/logs/app.log`; set `DEBUG=true` in `.env` for details.

## Further reading

- [README.md](README.md) — full, human-oriented guide (same topics, more examples).
- `vendor/lexislav/station0/docs/visibility.md` — publication, scheduling, inheritance.
- `vendor/lexislav/station0/docs/plugins.md` — plugin config and API.
- `vendor/lexislav/station0/docs/redirects.md` — the redirect manager.
- `vendor/lexislav/station0/README.md` — library README (select options, thumbnails, access, actions in depth).
- `vendor/lexislav/station0/CHANGELOG.md` — what each version added.
- `vendor/lexislav/station0/CLAUDE.md` — core internals; only needed when a
  question can't be answered from the files above.

## Developing against a local Station0 clone

To test unreleased core changes, point Composer at a local clone (don't
commit this):

```json
"repositories": [{"type": "path", "url": "../station0"}],
"require": {"lexislav/station0": "*@dev"}
```

then `composer update lexislav/station0`.
