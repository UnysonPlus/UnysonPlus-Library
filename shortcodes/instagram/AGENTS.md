---
type: shortcode
name: instagram
version: 2.0.0
provides: leaf
tab: Media Elements
distribution: Shortcodes Library (UnysonPlus-Library/shortcodes/instagram) — not bundled in core
---

# Instagram Feed

An Instagram profile header (picture, name, @handle, Follow button) above the profile's posts, shown
as a grid or as a paged slider with arrows. Leaf shortcode (`instagram` → `[instagram]`).

**Shortcodes Library element, not bundled in core.** Installed from Unyson+ → Shortcodes → Shortcodes
Library → *Install* (into the active theme's `framework-customizations/extensions/shortcodes/shortcodes/instagram/`),
or from the `instagram.zip` via *Upload a .zip* (into `uploads/unysonplus/shortcodes/instagram/`).

**v1 compatibility.** Pages saved with v1.x stored flat atts (`username`, `access_token`, `count`, `columns`, `gap`
as a Gap Scale slug, `aspect.ratio`, `rounding`, `max_width`, `align`, `hover_zoom`, `link_to_post`, `new_tab`).
When `source` is absent and `access_token`/`username` is present, the view maps them onto v2 (account source,
grid layout, no frame), so those pages keep their feed.

Two sources (`source/mode`):

- **`account`** — the latest posts of a connected Instagram **Business or Creator** account, read
  from the Instagram API (Instagram API with Instagram Login) with a long-lived access token.
  Personal accounts cannot share their media with any API. The profile, the post list and a
  resized local copy of every image are stored on the site, so the feed is fast and keeps showing
  the last good copy if Instagram is down or the token fails.
- **`manual`** — images picked from the Media Library, each with its own link and post-type icon.
  Needs no account connection.

## Options schema (atts)

| Att | Type | Default | Description |
|-----|------|---------|-------------|
| `source` | multi-picker | `{mode:'account'}` | `source/mode`: `account` \| `manual`. |
| `source/account/access_token` | password | `''` | Long-lived Instagram User access token (Business/Creator account). Never output on the page. |
| `source/account/cache_hours` | select | `'6'` | `1` `3` `6` `12` `24` — how long a fetched copy stays fresh before a background refresh. |
| `source/manual/posts` | addable-popup | `[]` | Each `{ image:{attachment_id,url}, link, kind:'image'\|'video'\|'album', alt }`. Empty `link` → profile link. |
| `show_header` | switch | `'yes'` | Profile header on/off. |
| `profile_handle` | text | `''` | `@handle` (the `@` is optional). Builds `https://www.instagram.com/<handle>/` for the header and Follow links. Account mode fills it from the API when empty. |
| `profile_name` | text | `''` | Bold display name. Empty → the account's name, else the handle. |
| `profile_avatar` | upload | `''` | Profile picture. Empty → the account's own picture (account mode). |
| `follow_label` | text | `'Follow'` | Button text. Empty hides the button. |
| `button_preset` | button-style-picker | `''` | None = Instagram-blue button; a preset = the site's `.btn` + preset classes. |
| `layout` | select | `'slider'` | `grid` (all posts at once) \| `slider` (horizontal pages, arrows). |
| `count` | select | `'16'` | `4` `6` `8` `9` `12` `16` `20` `24` posts. |
| `rows` | select | `'2'` | Slider only: rows per page (`1`–`3`). |
| `columns` / `columns_tablet` / `columns_mobile` | select | `'4'` / `'3'` / `'2'` | Columns per breakpoint (tablet ≤ 991px, phone ≤ 599px). |
| `ratio` | select | `'1-1'` | Tile shape: `1-1` \| `4-5` \| `9-16`. |
| `gap` | select | `'0'` | `0` `2` `6` `12` `20` px between tiles (v1 Gap Scale slugs still resolve). |
| `rounding` | select | `'none'` | `none` `small` `medium` `large` tile corners. |
| `max_width` / `align` | unit-input / select | empty / `'center'` | Optional maximum width and its alignment. |
| `show_type_icon` | switch | `'yes'` | Reel / album icon in the tile corner. |
| `hover` | select | `'zoom'` | `zoom` \| `darken` \| `none`. |
| `new_tab` | switch | `'yes'` | Open Instagram links in a new tab. |
| `frame` | switch | `'yes'` | White panel behind the header (an Instagram-profile look). |
| + Animations, Advanced tabs | — | — | `sc_get_animation_fields()`, `sc_get_advanced_tab()`. |

### Ready-to-use example (manual source)

```json
{
  "source": { "mode": "manual", "manual": { "posts": [
    { "image": { "attachment_id": "13", "url": "https://example.com/pool.jpg" }, "link": "https://www.instagram.com/p/XXXX/", "kind": "video", "alt": "Sunset over the pool" },
    { "image": { "attachment_id": "9", "url": "https://example.com/bedroom.jpg" }, "link": "", "kind": "image", "alt": "" }
  ] } },
  "profile_handle": "yourvilla", "profile_name": "Your Villa", "follow_label": "Follow",
  "layout": "slider", "count": "16", "rows": "2", "columns": "4", "columns_tablet": "3", "columns_mobile": "2",
  "ratio": "1-1", "gap": "0", "show_type_icon": "yes", "hover": "zoom", "new_tab": "yes", "frame": "yes"
}
```

## Rendering

`<div class="igf igf--{grid|slider} igf--hover-{…} [igf--frame]" style="--igf-cols…">` →
`.igf__head` (`.igf__who` link with `.igf__avatar` + `.igf__name` / `.igf__handle`, and the
`.igf__follow` link) → `.igf__viewport` → `ul.igf__track` of `li.igf__item > a.igf__tile` (an
`<img class="igf__img">` — `wp_get_attachment_image()` with srcset for library images —
`.igf__type` icon and `.igf__overlay`) → slider arrows `.igf__arrow--prev/--next`.

Every tile and arrow is server-rendered (the Asset Optimizer's unused-CSS pass keeps every rule).
The slider is a CSS scroll-snap grid (`grid-auto-flow: column`); `static/js/instagram.js` only
reveals the arrows when there is overflow and pages by one viewport width, so swipe works without it.
With no posts, visitors see nothing and logged-in editors see a note saying why.

## Account data (class file)

`FW_Shortcode_Instagram` (`class-fw-shortcode-instagram.php`, loaded at registration):

- `get_feed( $token, $count, $cache_hours )` → `{ profile:{username,name,avatar,followers,posts}, items:[{id,type,image,link,caption,time}], requested, fetched, error? }`.
- Stored per token hash (`key()` = first 16 hex of md5): option `fw_igf_data_<hash>` (the copy),
  option `fw_igf_tokens` (`hash → {token, refreshed, count, hours}`), transient `fw_igf_fresh_<hash>`
  (fresh flag), transient `fw_igf_lock_<hash>` (fetch lock).
- First render with no copy fetches inline; afterwards a stale copy triggers the one-off cron event
  `fw_igf_refresh` (arg: hash) and visitors keep the stored copy. A failed fetch keeps the old copy and
  pauses 30 minutes.
- Token refresh: `GET /refresh_access_token?grant_type=ig_refresh_token` when the stored token is
  older than 7 days; the new token replaces it under the same hash.
- Images (and the avatar) are copied into `uploads/unysonplus/instagram/` via `fw_upw_uploads_dir()`,
  resized to 720px (avatar 160px), because Instagram's media URLs expire.
- Filter `fw_instagram_feed_api_base` (default `https://graph.instagram.com`) pins an API version or
  points tests at a mock.

## Pitfalls

- A personal Instagram account cannot be used in `account` mode — switch it to Business or Creator
  (free, in Instagram's settings) and generate the token in the Meta developer dashboard.
- Changing the token creates a new hash: the old copy stays in the database until removed.
- `wp_safe_remote_get()` refuses local addresses, so a local mock API needs the
  `http_request_host_is_external` filter in the test.

## Files

- `class-fw-shortcode-instagram.php` — API access, caching, token refresh, image copies
- `config.php` — builder tab, title, canvas chip
- `options.php` — atts schema
- `static.php` — enqueues the CSS + JS (works out its own URL, so it runs from uploads, a theme or a plugin)
- `views/view.php` — front-end HTML
- `static/css/instagram.css`, `static/js/instagram.js`, `static/img/page_builder.svg`
