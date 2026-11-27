---
title: One-shot "wipe the demo" action — start from a blank site once you know the system
status: open
priority: medium
area: skeleton
created: 2026-10-27
related: [site/tasks/, site/content/, site/templates/, site/actions/contact.php, site/config.php, public/assets/site.css, AGENTS.md, README.md]
---

## Problem

`composer create-project lexislav/get-station0` gives a site full of demo
content. It's useful for learning: `/guide`, `/work`, `/shop`, `/blog`,
`/studio`, `/members`, `/contact`, five collections, eight block types,
`_redirects.yaml`, the banners bar in `layout.twig`. Once the user knows the
system, though, there is no clean way to get rid of it. They have to delete
pages, collections, templates, blocks, the contact action and their CSS by
hand. It's easy to miss things, such as a `collection('banners')` call left
in `layout.twig` that still points at a deleted collection.

## Why it matters / why deferred

Every real site built from the skeleton starts by doing this cleanup. Doing
it by hand is slow and leaves odd leftovers behind. Not done yet because the
demo content is still changing.

## Proposed fix

- A site task `site/tasks/wipe-demo.php` (runs from the console and the
  admin) with a required confirm param, e.g. `--confirm=WIPE`, and a
  `--dry-run` that lists what would be removed.
- It removes the demo pages, collections, block types (except `text`), page
  templates and manifests, the `contact` action, `_redirects.yaml` rules and
  `_groups.yaml` entries. It leaves behind a minimal working site: homepage
  `page.txt`, `layout.twig` with no demo partials or banners, `page.twig`,
  `404.twig` / `410.twig`, and a reset `site.css`.
- Leave alone: `.env`, `site/config.php` (maybe reset the `access` /
  `plugins` demo entries), user accounts in `writable/db.sqlite`, and
  `vendor/`. Run `cache:clear` at the end.
- Optional: remove the task itself (and its own entry in AGENTS.md /
  README) as the last step, so a blank site has no "wipe" button left.
- Pitfall: an explicit list of what to remove, not "everything except…".
  Otherwise it will delete things the user already added next to the demo.
  Keep the list next to the demo content so it's updated whenever the demo
  changes.

## Done when

- `php vendor/bin/console task:run wipe-demo --confirm=WIPE` on a fresh
  create-project leaves a site where `/` answers 200, the admin opens with
  no errors, and `writable/logs/app.log` is clean.
- `--dry-run` lists every path it would remove.
- README and AGENTS.md say when and how to run it.
