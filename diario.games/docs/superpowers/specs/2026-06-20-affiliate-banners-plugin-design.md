# Diario.Games — Affiliate Banners Plugin (`alv-aff-banners`)

## Overview

Add a full-width affiliate banner below the "2 rows of games list" on the homepage and below the genre title on genre pages. The banner uses the [Instant Gaming Partner Banner API](https://www.instant-gaming.com/en/docs/banners/), which auto-generates rotating featured-game deals and embeds the affiliate `igr` so we earn commissions on clicks.

The feature is shipped as a self-contained Kirby plugin, `alv-aff-banners`, that registers:

- A reusable snippet (`affiliate-banner`)
- The Instant Gaming loader script + `igBannerConfig` injected via Kirby hooks
- Plugin options for affiliate ID, language, and banner selector

No changes to existing templates' markup are required beyond inserting the new snippet call. The plugin keeps all affiliate config, markup, and script injection in one folder so it can be enabled/disabled independently.

## Tech Changes

| Component | Change |
|-----------|--------|
| New plugin | `site/plugins/alv-aff-banners/` — registers snippet + hooks |
| Snippets | `affiliate-banner` (full-width IG banner placeholder) |
| Hooks | `page.render:before` (or direct snippet call) injects `igBannerConfig` + loader script |
| Config | New `alvaffbanners.igr` option, read from `.env` via `INSTANT_GAMING_IGR` |
| Templates | `home.php` and `genre.php` get a single `snippet('affiliate-banner')` call |
| `.env.example` | Add `INSTANT_GAMING_IGR=` |

## Instant Gaming Banner API Recap

From the official docs:

1. Set `window.igBannerConfig = { lang, igr, banners: ['<selector>'] }` **before** the loader.
2. Load `https://www.instant-gaming.com/api/banner/partner/loader.js` (deferred).
3. Place `<div class="<selector>"></div>` wherever the banner should render.
4. Banner is responsive — adapts to desktop/mobile and auto-updates with featured deals.

The `igr` (Instant Gaming Referral ID) is the affiliate ID, editable at https://www.instant-gaming.com/en/partnership/.

## Architecture

```
site/plugins/alv-aff-banners/
├── index.php              # Kirby plugin registration: options, snippets, hooks
├── snippets/
│   └── affiliate-banner.php  # Full-width banner div + optional extra store slots
├── composer.json          # kirby-plugin manifest
└── README.md              # Plugin usage + setup instructions
```

### Plugin registration (`index.php`)

The plugin only registers options + the snippet. Script tags live inside the snippet itself (see below) so the plugin is fully self-contained and requires no edits to `header.php`. No hooks are needed.

```php
<?php

use Kirby\Cms\App;

App::plugin('alv/aff-banners', [
    'options' => [
        'igr'     => env('INSTANT_GAMING_IGR', ''),
        'lang'    => 'es',
        'banners' => ['ig-aff-banner'],
        'enabled' => true,
    ],
    'snippets' => [
        'affiliate-banner' => __DIR__ . '/snippets/affiliate-banner.php',
    ],
]);
```

### Snippet (`snippets/affiliate-banner.php`)

```php
<?php
/** @var string $slot Optional slot key for future additional stores */

if (!option('alv.aff-banners.enabled', true)) return;
$igr = option('alv.aff-banners.igr', '');
if (!$igr) return;

$selector = option('alv.aff-banners.banners', ['ig-aff-banner'])[0] ?? 'ig-aff-banner';
$lang     = option('alv.aff-banners.lang', 'es');
$banners  = json_encode(option('alv.aff-banners.banners', ['ig-aff-banner']));
?>
<section class="alv-aff-banner-wrapper -mx-4 px-4 py-6 my-6" aria-label="Ofertas de juegos">
    <div class="max-w-7xl mx-auto mb-3 flex items-center justify-between">
        <p class="text-xs uppercase tracking-widest text-muted">
            Ofertas destacadas
        </p>
        <span class="text-[10px] text-muted/60">Patrocinado · Instant Gaming</span>
    </div>

    <script>
      window.igBannerConfig = {
        lang: '<?= htmlspecialchars($lang, ENT_QUOTES) ?>',
        igr: '<?= htmlspecialchars($igr, ENT_QUOTES) ?>',
        banners: <?= $banners ?>
      };
    </script>
    <script src="https://www.instant-gaming.com/api/banner/partner/loader.js" defer></script>

    <div class="<?= htmlspecialchars($selector) ?> min-h-[120px] w-full"></div>
</section>
```

Why the script tags live inside the snippet:
- They run **before** the banner div in the DOM order, satisfying IG's "config before loader" requirement.
- The loader is `defer`-ed so it executes after parse but still has access to `window.igBannerConfig`.
- This keeps the plugin fully self-contained — no edits to `header.php` are required.

### CSS (full-width breakout)

The wrapper uses Tailwind utility classes already available in the theme:

- `-mx-4` cancels the parent `<main>` `px-4` padding
- `w-[calc(100vw)]` + `left-1/2 -translate-x-1/2` would break out of `max-w-7xl`; we use `-mx-4` + inner `max-w-7xl mx-auto` instead to keep it simpler and to match the existing visual rhythm (same pattern as `<header>` and `<footer>`)

The Instant Gaming banner inside the wrapper auto-adapts to the wrapper width, so no further CSS is needed.

## Integration points

The banner repeats **below every even row** of games, not just once at the bottom. On responsive grids we anchor "even row" to the largest breakpoint (2 rows worth of items) and render the banner as a `col-span-full` grid item so it spans full width at all breakpoints.

### Snippet modes

The `affiliate-banner` snippet accepts a `$grid` boolean:
- `false` (default) — standalone full-width section with `-mx-4` breakout and label header
- `true` — grid item with `col-span-full` for in-grid insertion (no breakout margins, no label header)

A `static $scriptEmitted` guard ensures `igBannerConfig` + the loader `<script>` are emitted only once even when the snippet is called multiple times on the same page. The IG loader is `defer`-ed so it runs after all placeholder divs exist in the DOM.

### Homepage — `site/templates/home.php`

The genre grid is `md:grid-cols-2`, so an even row = 2 rows = 4 genre cards. The banner is inserted after every 4 genre cards as a `col-span-full` grid item:

```php
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php
    $bannerEvery = 4;
    $i = 0;
    foreach ($genreGames as $genre => $games):
        $i++;
    ?>
        ...genre card...
    <?php
        if ($i % $bannerEvery === 0):
            snippet('affiliate-banner', ['grid' => true]);
        endif;
    endforeach;
    ?>
</div>
```

### Genre page — `site/templates/genre.php`

The games grid is `xl:grid-cols-4`, so an even row = 2 rows = 8 games. The banner is inserted after every 8 games as a `col-span-full` grid item:

```php
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php
    $bannerEvery = 8;
    $i = 0;
    foreach ($games as $game):
        $i++;
        snippet('game-card', ['game' => $game]);
        if ($i % $bannerEvery === 0):
            snippet('affiliate-banner', ['grid' => true]);
        endif;
    endforeach;
    ?>
</div>
```

On the genre page the banner only appears when there are games (it lives inside the games grid, so the empty-state branch shows no banner).

## Config

### Panel (primary)

All affiliate configuration is managed visually in the Kirby Panel at **Site → Affiliate Banners**:

| Field | Type | Description |
|-------|------|-------------|
| `alv_aff_banner_enabled` | toggle | Master on/off for all banners |
| `alv_aff_banner_frequency` | select (1-5) | Show banner every N rows |
| `alv_aff_programs` | structure | Repeatable entries, one per affiliate program |

Each **program entry** has:

| Field | Type | Description |
|-------|------|-------------|
| `name` | text | Display name (e.g. "Instant Gaming") |
| `enabled` | toggle | Per-program on/off |
| `type` | select | `instant-gaming` (IG Banner API) or `generic` (link) |
| `affiliate_id` | text | For IG: your `igr` referral ID. For generic: full URL |
| `banner_label` | text | Header text (default: "Ofertas destacadas") |
| `banner_sponsor` | text | Subtitle text (default: "Patrocinado") |

The `site()->alvAffBanners()` site method returns the parsed config:

```php
[
    'enabled'   => true,
    'frequency' => 2,
    'programs'  => [
        ['name' => 'Instant Gaming', 'enabled' => true, 'type' => 'instant-gaming', 'affiliate_id' => 'abc123', ...],
        ['name' => 'Other Store', 'enabled' => false, 'type' => 'generic', 'affiliate_id' => 'https://...', ...],
    ],
]
```

### `.env` (removed)

The `INSTANT_GAMING_IGR` env var is no longer used. All config is stored in the Panel.

## Failure modes

| Condition | Behavior |
|-----------|----------|
| Banners disabled in Panel | Snippet returns early — no markup, no script tags |
| No programs configured | Snippet returns early — no markup |
| All programs disabled | Snippet returns early — no markup |
| IG loader script fails to load (network/CDN issue) | Wrapper renders empty (min-height `120px`), no crash |
| `igr` invalid | Banner still renders but clicks won't be attributed — silent, no error |

## Scope boundaries (YAGNI)

- **No** admin Panel field for editing `igr` — env var is enough for now
- **No** click/conversion tracking — IG tracks that on their side
- **No** additional affiliate stores (Humble, Fanatical, etc.) in this iteration — the wrapper is ready for them via an optional `$slot` param, but only Instant Gaming is wired up
- **No** lazy-loading or visibility tracking — IG's loader already defers via `defer` attribute

## Testing

1. Go to Panel → Site → Affiliate Banners
2. Enable banners, set frequency to 2
3. Add an Instant Gaming program with your `igr` ID
4. Visit homepage `/` — banner appears below every 2nd row of genre cards
5. Visit any `/genre/<slug>` — banner appears below every 2nd row of games (every 8 games)
6. Inspect the rendered `<div class="ig-aff-banner">` — IG's loader should populate it with rotating deals
7. Disable the master toggle — no banner markup appears, no JS errors
8. Disable a specific program — only that program's banner disappears
9. Test on mobile width (~375px) — banner stays full-width and adapts
10. Change frequency to 3 — banners now appear every 3 rows (every 6 games on genre page)

## Future extension (out of scope now)

- Add more store banners (Humble Bundle, Fanatical, Green Man Gaming) — extend the snippet with conditional `$slot` blocks reading from separate options
- Panel field to edit `igr` from the Kirby backend
- Lazy-load the IG script only when the banner is in viewport (improves initial LCP)
