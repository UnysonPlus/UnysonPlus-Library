# UnysonPlus-Templates

The **data repository** for the UnysonPlus **Template Library** extension
(`UnysonPlus-Template-Library-Extension`). It holds the premade page-builder
templates users can browse and install from **Unyson+ → Template Library** inside
WordPress.

This repo contains **no code** — only a catalog and template JSON. The extension
fetches `catalog.json` from this repo's raw content, and downloads each template's
export envelope on demand into the site's `wp-content/uploads/unysonplus-templates/`
folder.

## Layout

```
catalog.json                         # the manifest of installable templates
templates/<slug>/<slug>.json         # the page-builder export envelope for <slug>
templates/<slug>/thumb.svg           # a preview thumbnail for the card (svg/png/webp)
```

## catalog.json

```json
{
  "version": 1,
  "base_url": "https://raw.githubusercontent.com/UnysonPlus/UnysonPlus-Templates/master/",
  "templates": {
    "stats-band": {
      "slug": "stats-band",
      "title": "Stats Band",
      "category": "Stats",
      "kind": "section",
      "description": "…",
      "thumb": "templates/stats-band/thumb.svg"
    }
  }
}
```

- **`base_url`** — the raw-content base the extension resolves `templates/…` and
  `thumb` paths against. Keep the trailing slash.
- **`kind`** — `section`, `column`, or `full`. Determines which of the builder's
  Templates tabs the template appears under.
- **`thumb`** — a path (relative to `base_url`) to a preview image.

## Adding a template

1. In the WordPress page builder, build the section/column/page.
2. Use **Templates → Export** (the download icon on a section) to get its
   `.json` export envelope (`{ "_fw_template_export": … , "json": "…" }`).
3. Save it as `templates/<slug>/<slug>.json`.
4. Add a `thumb.svg` (or `.png`/`.webp`) preview beside it.
5. Add an entry to `catalog.json`.
6. Commit + push. Users see it after the extension's 12-hour catalog cache
   refreshes (or when they click **Refresh catalog**).

### Important — fresh `unique_id`s

The page builder does **not** regenerate a template's element `unique_id`s on
insert. Author each template with unique 32-hex `unique_id`s (an export from a real
builder page already satisfies this) so two templates never collide on the same
page.
