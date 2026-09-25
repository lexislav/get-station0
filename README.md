# get-station0

Starter kit for **[Station0](https://github.com/lexislav/station0)**, a lightweight flat-file CMS for PHP 8.2+.

Your pages are plain text files in `site/content/`. There are no database migrations and the whole site is Git-friendly. SQLite is used only for admin logins. Editors get a friendly admin with a block-based page builder, and web designers work with plain Twig templates and a few YAML files.

This repository is the **project skeleton**. The CMS itself (`lexislav/station0`) is installed into `vendor/` and updated independently, so your site files are never touched by an update.

---

- [Quick start](#quick-start)
- [What's inside](#whats-inside)
- [Content: pages](#content-pages)
- [Templates](#templates)
- [Blocks (the page builder)](#blocks-the-page-builder)
- [Template manifest: fields, blocks, restrictions](#template-manifest-fields-blocks-restrictions)
- [Streams (blogs, news, listings)](#streams-blogs-news-listings)
- [Collections (headless content)](#collections-headless-content)
- [Media & uploads](#media--uploads)
- [Field types reference](#field-types-reference)
- [Twig reference](#twig-reference)
- [Users & roles](#users--roles)
- [Configuration](#configuration)
- [Command line](#command-line)
- [Deployment](#deployment)
- [Updating Station0](#updating-station0)

---

## Quick start

**Requirements:** PHP ≥ 8.2 with the `pdo_sqlite` extension, and Composer.

```bash
composer create-project lexislav/get-station0 mysite
cd mysite
php -S localhost:8080 -t public public/index.php
```

1. Open <http://localhost:8080>. The demo site is there.
2. Open <http://localhost:8080/admin>. The first visit shows a **setup form** that creates the first admin account. Once a user exists, the form disables itself.
3. Edit a page in the admin, save it, and reload the site.

`composer create-project` copies `.env.example` to `.env` for you. Start the dev server from the project root.

---

## What's inside

```
mysite/
├── public/                 ← web root (index.php, .htaccess, assets/)
│   └── assets/site.css
├── site/
│   ├── config.php          ← app configuration
│   ├── content/
│   │   ├── pages/          ← the page tree (one directory per page)
│   │   └── collections/    ← headless content (banners, shared blocks…)
│   └── templates/
│       ├── layout.twig     ← base layout
│       ├── page.twig       ← one file per page template
│       ├── blog.twig, article.twig, …
│       └── blocks/         ← page-builder block types
│           ├── text/
│           └── gallery/
├── writable/               ← cache, sessions, logs, db.sqlite (created automatically)
├── vendor/                 ← Station0 core + dependencies
└── .env                    ← environment settings
```

**Demo content** shows each feature:

| URL | Template | Shows |
|---|---|---|
| `/` | `page` | Plain Markdown page |
| `/history` | `blocks` | Page builder: `text` + `gallery` blocks, page-local image |
| `/history/about`, `/history/team` | `page` | Nested pages |
| `/work` | `blocks` | Blocks with external image URLs |
| `/blog` | `blog` | A **stream** that lists its child articles |
| `/blog/…` | `article` | Articles with `PublishedAt` and `Author` |
| `/studio` | `showcase` | Content pulled from **collections** |

The banner bar above the header comes from the `banners` collection (see `layout.twig`).

---

## Content: pages

Every page is a directory under `site/content/pages/`. The directory name is the URL slug, and the page tree is the directory tree:

```
site/content/pages/
├── page.txt                  → /
├── history/
│   ├── page.txt              → /history
│   ├── 6715896b8f477dc8.png  ← an image that belongs to this page
│   └── team/
│       └── page.txt          → /history/team
└── blog/
    ├── blog.txt              → /blog          (template "blog")
    └── editing-basics/
        └── article.txt       → /blog/editing-basics
```

The content file is named after the page's **template** (`page.txt`, `blog.txt`, `article.txt`…), and `site/templates/<template>.twig` renders it.

A content file is **front matter**, then `---`, then the **body**:

```
Title: Editing content the flat-file way
Template: article
Published: true
PublishedAt: 2026-05-10 09:00
Author: Station0 Team
Sort: 10
---

The body: Markdown, or a YAML list of blocks (see below).
```

| Key | Meaning |
|---|---|
| `Title` | Page title (required) |
| `Metatitle` | Optional `<title>` override |
| `Template` | Template name (also implied by the file name) |
| `Published` | `true` / `false`. Drafts are hidden on the site but visible in the admin. |
| `PublishedAt` | `Y-m-d H:i`. A future date schedules the page. |
| `Author`, `Updated` | Informational; `Updated` is set on save |
| `Sort` | Order among siblings (lower first; otherwise alphabetical). Drag & drop in the admin sets it. |
| `AllowedChildTemplates` | Turns the page into a **stream** (see below) |

Any other key you add (for example `Subtitle: …`) is kept and available in Twig as `page.extra.subtitle`. Keys are case-insensitive and lower-cased on read. For fields that editors should manage, use [page fields](#page-fields).

You can edit these files by hand, commit them, or deploy them with Git. The admin reads and writes the same files.

---

## Templates

Page templates are Twig files in `site/templates/`. They usually extend `layout.twig`:

```twig
{# site/templates/page.twig #}
{% extends 'layout.twig' %}

{% block title %}{{ page.metatitle ?? page.title }} · {{ app.name }}{% endblock %}

{% block body %}
<article>
    <h1>{{ page.title }}</h1>
    <div class="content">{{ content|raw }}</div>
</article>
{% endblock %}
```

Every page template receives:

| Variable | Content |
|---|---|
| `page` | The page: `title`, `metatitle`, `urlPath`, `slug`, `template`, `published`, `publishedAt`, `author`, `updated`, `sort`, `extra` |
| `content` | The rendered body as HTML (Markdown or blocks), printed with `|raw` |
| `fields` | The template's [page fields](#page-fields): typed, with image URLs resolved |
| `app` | `app.name`, `app.baseUrl`, `app.adminPath` |

**Adding a template:** create `site/templates/<name>.twig`. It then appears in the admin's *Template* dropdown. `layout.twig` is never offered as a page template.

See the [Twig reference](#twig-reference) for the helper functions (`child_pages()`, `collection()`, …).

---

## Blocks (the page builder)

The admin edits a page body as an ordered list of **blocks**. On disk, the body is a YAML list:

```yaml
- type: text
  body: |
    # About us
    Some **Markdown**.
- type: gallery
  columns: 3
  images:
    - src: photo.jpg
      alt: Photo
```

A plain Markdown body also works: it's treated as a single `text` block.

The built-in `text` block is a Markdown editor. Every other block type is a directory in `site/templates/blocks/` with two files:

```
site/templates/blocks/gallery/
├── schema.yaml     ← the fields the admin shows
└── template.twig   ← how the block renders
```

```yaml
# schema.yaml
label: Gallery
fields:
  columns:
    type: number
    label: Columns
    default: 3
  images:
    type: list
    label: Images
    item:
      src:
        type: image
        label: Image
      alt:
        type: text
        label: Alt text
```

```twig
{# template.twig — block values are under `block` #}
<div class="gallery gallery--cols-{{ block.columns|default(3) }}">
    {% for image in block.images|default([]) %}
        <img src="{{ image.src }}" alt="{{ image.alt|default('') }}">
    {% endfor %}
</div>
```

**Adding a block type:** create the directory with both files. It appears in the admin's "+ Add block" bar immediately. Image values are stored as bare file names and arrive in `template.twig` as full `/media/…` URLs.

All field types are listed in the [field types reference](#field-types-reference).

---

## Template manifest: fields, blocks, restrictions

Each page template can have an optional **manifest** next to it, `site/templates/<template>.blocks.yaml`, which shapes the editor for pages that use that template. All keys are optional:

```yaml
# site/templates/product.blocks.yaml
fields:               # page fields: single values edited above the builder
  subtitle:
    type: text
    label: Subtitle
blocks: false         # hide the page builder completely
allowedBlocks:        # limit the "+ Add block" bar (in this order)
  - text
  - gallery
defaultBlocks:        # blocks pre-inserted into a NEW page
  - gallery
```

Without a manifest, a template gets the full block palette and a new page starts with one empty `text` block.

### Page fields

*(station0 ≥ 0.7.5)* Page fields suit **structured single values** such as a subtitle, price, hero image, dates or a list of features. They sit on the page itself, above the builder, and templates read them directly:

```yaml
# site/templates/product.blocks.yaml
fields:
  subtitle:
    type: text
    label: Subtitle
  price:
    type: number
    label: Price
  featured:
    type: boolean
    label: Featured
  hero:
    type: image
    label: Hero image
  features:
    type: list
    label: Features
    item:
      title:
        type: text
        label: Title
      text:
        type: textarea
        label: Text
```

```twig
{# site/templates/product.twig #}
{% extends 'layout.twig' %}
{% block body %}
<article>
    {% if fields.hero %}<img src="{{ fields.hero }}" alt="">{% endif %}
    <h1>{{ page.title }}</h1>
    <p class="lead">{{ fields.subtitle }}</p>
    {% if fields.featured %}<span class="badge">Featured</span>{% endif %}
    <p class="price">{{ fields.price }} €</p>
    <ul>
        {% for f in fields.features %}<li><strong>{{ f.title }}</strong> {{ f.text }}</li>{% endfor %}
    </ul>
    {{ content|raw }}   {# the builder, if the template keeps it #}
</article>
{% endblock %}
```

Three combinations are possible:

| Manifest | Editor shows |
|---|---|
| no `fields` | the page builder only (default) |
| `fields` | page fields **and** the builder |
| `fields` + `blocks: false` | page fields only (a "form-style" page) |

Details:

- Field values are stored in the page's front matter. Multi-line text and lists are written as indented YAML, so the files stay readable:
  ```
  Subtitle: Light and fast
  Features:
    - title: Grip
      text: Sticky sole
  ---
  ```
- `fields` in Twig are **typed**: booleans are `true`/`false`, numbers are numbers, and `image`/`file` values are ready-to-use `/media/…` URLs (also inside list items).
- For *another* page, such as children in a listing, use `page_fields(child)`:
  ```twig
  {% for child in child_pages(page.urlPath) %}
      {{ child.title }}: {{ page_fields(child).price }} €
  {% endfor %}
  ```
- A new page starts from the schema `default` values.
- Field names must not clash with built-in keys (`title`, `template`, `published`, `sort`, `body`, …); such fields are ignored. Keys are case-insensitive on disk, so prefer `snake_case` names.

### Allowed and default blocks

- `allowedBlocks` limits the palette, in the order you list. Unknown names are ignored.
- `defaultBlocks` pre-inserts blocks, with their schema defaults, into a **new** page. Each must be part of `allowedBlocks` if you set it.

> The editor is prepared for the template the page opens with. If you change the *Template* dropdown on a new page, save once to get that template's fields and palette.

---

## Streams (blogs, news, listings)

A **stream** is a page that holds a list of child entries, such as a blog, news or projects. Add `AllowedChildTemplates` to the parent page:

```
Title: Blog
Template: blog
AllowedChildTemplates: article
---
```

- New children of `/blog` can only use the `article` template (several templates: `article, event`).
- The admin gets a **Streams** tab for quick access to stream entries.
- The parent template lists its children:

```twig
{% for child in child_pages(page.urlPath) %}
    <a href="{{ child.urlPath }}">{{ child.title }}</a>
    {% if child.publishedAt %}{{ child.publishedAt|date('j. n. Y') }}{% endif %}
{% endfor %}
```

`child_pages()` returns only live pages (published and not scheduled for the future), in `Sort` order.

---

## Collections (headless content)

**Collections** are content with **no URL** of their own, such as banners, testimonials, team members or reusable snippets. Editors manage them in the admin, and templates pull them in wherever needed.

```
site/content/collections/
├── _groups.yaml                 ← optional: admin menu groups
└── banners/
    ├── _collection.yaml         ← optional schema
    └── summer-sale/
        ├── item.txt             ← same format as a page
        └── bg.jpg
```

```yaml
# _collection.yaml
label: Banners
group: Marketing          # optional: own admin menu tab
fields:
  subtitle:
    type: text
    label: Subtitle
  cta_url:
    type: text
    label: CTA URL
  color:
    type: color
    label: Accent color
```

Without a schema, items are free-form (title + body).

```twig
{% for banner in collection('banners') %}          {# published items, in Sort order #}
    <div style="border-color: {{ banner.extra.color }}">
        <strong>{{ banner.title }}</strong> {{ banner.extra.subtitle }}
    </div>
{% endfor %}

{% set cta = collection_item('shared-blocks', 'intro-cta') %}
{% if cta %}{{ render_collection_item(cta) }}{% endif %}   {# renders the body #}
```

Collection field values are available as `item.extra.<field>`.

### Admin menu groups

`group: <Name>` moves a collection out of the generic *Collections* tab into its own admin tab. The optional `site/content/collections/_groups.yaml` sets each group's label, icon, the roles that can see it, and the tab order:

```yaml
marketing:            # group id = slug of the group name
  label: Marketing
  icon: "📣"
  roles: [admin]      # admins always have access
```

---

## Media & uploads

Uploaded files are stored **next to the page** they belong to, in the page's directory, and content refers to them by bare file name (`team.jpg`). Moving or renaming a page takes its files along, and the references keep working.

- Public URL: `/media/<page-path>/<file>` (the homepage uses `/media/~/<file>`). Station0 builds these URLs for you in blocks, page fields and Markdown images.
- Images: jpg, png, gif, webp, svg (max 8 MB). Documents (`file` fields): pdf, office formats, archives, audio/video…
- Collection items store their files in their own directories: `/media/_collections/<collection>/<item>/<file>`.
- A new page must be **saved once** before files can be uploaded to it.
- Absolute URLs (`https://…`, `/…`) are left untouched, so external images work too.

---

## Field types reference

Used in block `schema.yaml`, page `fields:` and collection `_collection.yaml`:

| Type | Editor | Value in Twig |
|---|---|---|
| `text` | one-line input | string |
| `textarea` | multi-line input | string (use `|nl2br` to keep line breaks) |
| `number` | number input (`min`, `max` optional) | number |
| `boolean` | checkbox | `true` / `false` |
| `select` | dropdown | the selected value |
| `color` | color picker + hex input | `#rrggbb` |
| `image` | upload button | `/media/…` URL |
| `file` | upload button (documents) | `/media/…` URL |
| `list` | repeatable rows of `item:` sub-fields | array of rows |

Common options: `label`, `default`.

A list whose items contain an `image` field gets drag & drop multi-upload, like the gallery block.

**Select options** can be static:

```yaml
size:
  type: select
  options: [s, m, l]            # or {s: Small, m: Medium} or [{value, label, group}]
```

…or come from a collection (value = item slug):

```yaml
product:
  type: select
  options_from: collection:products
  group_by: category            # optional <optgroup> by an item field
  sort_by: -price               # optional; "-" = descending
  option_label: "{title} ({price} €)"
  placeholder: "— choose —"
```

---

## Twig reference

| Function | Returns |
|---|---|
| `top_level_pages()` | Live first-level pages (for the main navigation) |
| `child_pages('/blog')` | Live direct children of a page, sorted |
| `page_fields(page)` | Typed, resolved page fields of any page |
| `collection('name')` | Published items of a collection |
| `collection_item('name', 'slug')` | One item, or `null` |
| `render_collection_item(item)` | The item body as HTML |

Globals: `app.name`, `app.baseUrl`, `app.adminPath`.

Rendered page and block HTML is cached in `writable/cache/`. The cache is cleared automatically whenever content is saved in the admin. After editing files by hand, run `php vendor/bin/console cache:clear`.

---

## Users & roles

| Role | Can |
|---|---|
| `admin` | everything, including **Users** and **Settings** |
| `editor` | pages, streams and collections (except groups restricted by `roles`) |

Create users in the admin (*Users*) or from the command line. Password reset by e-mail needs working SMTP settings.

---

## Configuration

`.env` (copied from `.env.example`):

| Variable | Default | Meaning |
|---|---|---|
| `BASE_URL` | `http://localhost:8080` | Public URL of the site |
| `DEBUG` | `true` | Error details and no Twig cache. **Set `false` in production.** |
| `ADMIN_PATH` | `/admin` | Where the admin lives |
| `HTTPS` | `false` | `true` marks the admin session cookie as secure. Set it when serving over HTTPS. |
| `ADMIN_LOCALE` | `cs` | Admin language: `en` or `cs` |
| `ADMIN_BLOCK_COLLAPSE` | `remember` | Initial block state in the editor: `remember`, `expanded`, `collapsed` |
| `SITE_PATH` | `./site` | Move `site/` elsewhere (absolute path) |
| `MAIL_*` | | SMTP for password-reset e-mails |

The site name (`name`), paths and session settings are set in `site/config.php`.

---

## Command line

Run from the project root:

```bash
php vendor/bin/console user:create <username> <email> [admin|editor]
php vendor/bin/console user:reset-password <email>
php vendor/bin/console cache:clear
php vendor/bin/console assets:relink --dry-run   # convert old absolute /media links to page-local names
```

---

## Deployment

1. Upload the project (or `git pull` it) and run `composer install --no-dev`.
2. Point the web server's **document root at `public/`**. Nothing outside it should be publicly reachable.
3. Make `writable/` and `site/content/` writable by the web server (the admin writes content files and uploads).
4. In `.env`: set `DEBUG=false`, the real `BASE_URL`, and `HTTPS=true` if applicable.
5. Visit `/admin` to create the first admin, or use `user:create`.

**Apache:** `public/.htaccess` already contains the rewrite rules. Enable `mod_rewrite` and `AllowOverride All`:

```apache
<VirtualHost *:80>
    ServerName mysite.local
    DocumentRoot "/path/to/mysite/public"
    <Directory "/path/to/mysite/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

If Apache shows its own "Not Found" page on `/admin`, the rewrite isn't applied. Check `mod_rewrite` and `AllowOverride`.

**nginx:**

```nginx
root /path/to/mysite/public;
location / {
    try_files $uri /index.php$is_args$args;
}
location ~ \.php$ {
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
}
```

**Backups:** `site/` holds your content and templates, and `writable/db.sqlite` holds your users. Keeping `site/` in Git is the easiest backup.

---

## Updating Station0

The CMS core is a normal Composer dependency:

```bash
composer update lexislav/station0
php vendor/bin/console cache:clear
```

Your `site/` directory is never touched. Check the [Station0 changelog](https://github.com/lexislav/station0/blob/main/CHANGELOG.md) for new features and upgrade notes.

---

## License

MIT
