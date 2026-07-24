# UnysonPlus-Library

The **data repository** for the UnysonPlus content libraries. It holds the premade
content that UnysonPlus extensions let users browse and install from inside
WordPress — **page-builder templates** today, with **presets** and **icon packs**
to follow.

This repo contains **no code** — only catalogs and content JSON. Each extension
fetches the `catalog.json` of the library type it owns and downloads individual
items on demand into the site's `wp-content/uploads/…` folder.

## Layout — one subfolder per library type

```
templates/       catalog.json + <slug>/<slug>.json + <slug>/thumb.*   ← page-builder templates
page-presets/    catalog.json + <slug>.json                            (planned)
header-presets/  catalog.json + <slug>.json                            (planned)
footer-presets/  catalog.json + <slug>.json                            (planned)
style-packs/     catalog.json + <slug>/…                               (planned)
icon-packs/      catalog.json + <slug>/…                               (planned)
```

Each subfolder is self-contained: its `catalog.json` `base_url` points at that
subfolder's raw URL, and item/thumbnail paths are relative to it. The matching
extension points its catalog URL at that subfolder, e.g.
`https://raw.githubusercontent.com/UnysonPlus/UnysonPlus-Library/master/templates/catalog.json`.

## catalog.json (templates example)

```json
{
  "version": 1,
  "base_url": "https://raw.githubusercontent.com/UnysonPlus/UnysonPlus-Library/master/templates/",
  "templates": {
    "stats-band": {
      "slug": "stats-band",
      "title": "Stats Band",
      "category": "Stats",
      "kind": "section",
      "description": "…",
      "thumb": "stats-band/thumb.svg"
    }
  }
}
```

- **`base_url`** — the raw-content base for this library type (keep the trailing slash).
- **`kind`** (templates) — `section`, `column`, or `full`.
- **`thumb`** — a path relative to `base_url`.

## Adding a template

1. In the WordPress page builder, build the section/column/page.
2. **Templates → Export** (download icon on a section) to get its `.json` export
   envelope (`{ "_fw_template_export": … , "json": "…" }`).
3. Save it as `templates/<slug>/<slug>.json`, add a `thumb.svg`/`.webp` beside it.
4. Add an entry to `templates/catalog.json`.
5. Commit + push. Users see it after the extension's 12-hour catalog cache
   refreshes (or on **Refresh catalog**).

### Fresh `unique_id`s

The page builder does **not** regenerate element `unique_id`s on insert. Author each
template with unique 32-hex `unique_id`s (a real builder export already satisfies
this) so two templates never collide on the same page.
