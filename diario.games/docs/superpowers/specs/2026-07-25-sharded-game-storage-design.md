# Sharded Game Storage for 100K+ Games

**Date:** 2026-07-25
**Status:** draft

## Problem

The current flat content structure (`content/games/{slug}/`) cannot scale past ~10K-30K games. At 100K+ entries, directory lookups on ext4 degrade to O(n) linear scans, Kirby's page discovery becomes catastrophically slow, and Git becomes unusable.

## Solution

Shard the `content/games/` directory tree by release date (`year/month/slug`), keeping each directory below ~5K entries. Use Kirby's native routing + a page model override to preserve clean root-level URLs (`/{slug}`).

## Architecture

### Filesystem Layout

```
content/games/
├── games.txt                          # games listing page (unchanged)
├── 00/                                # unknown release date
│   └── 00/
│       └── {slug}/
│           ├── game.txt
│           ├── {slug}.jpg
│           ├── {slug}-hero.jpg
│           ├── steam-capsule.jpg
│           ├── screenshot-N.jpg (+ .txt metadata)
│           └── video-N.jpg (+ .txt metadata)
├── {year}/                            # e.g. 2023
│   ├── 00/                            # year-only release date
│   │   └── {slug}/
│   └── {month}/                       # e.g. 09 (01-12)
│       └── {slug}/
└── {future-year}/                     # upcoming games
    └── {month}/
        └── {slug}/

data/games/
└── index.php                          # slug => "year/month" mapping array
```

### Slug Lookup Index (`data/games/index.php`)

```php
<?php return [
    'counter-strike-2'  => '2023/09',
    'dota-2'            => '2013/07',
    'mystery-game'      => '00/00',
    'some-game'         => '2023/00',
    'upcoming-game'     => '2025/12',
    // ...100K+ entries
];
```

- Generated atomically on each import batch (write to `.tmp` then `rename()`)
- ~3-4 MB for 100K entries — PHP `include` loads into opcache, sub-millisecond lookup
- Regenerated alongside content imports, never manually edited

### URL Resolution (`site/config/config.php`)

Two custom routes:

**Route 1 — Catch root-level slugs:**

```
Pattern:  '(:any)'
Action:   Check if slug is a known system page → skip via $this->next().
          Look up slug in index → resolve to 'games/{year}/{month}/{slug}'.
          Return page via site()->visit().
          Not found → $this->next() → 404.
```

**Route 2 — Redirect old nested URLs (SEO):**

```
Pattern:  'games/(:any)/(:any)/(:any)'
Action:   301 redirect to /{slug}.
```

### Page Model (`site/models/game.php`)

Override `url()` so `$page->url()` returns the clean root-level URL:

```php
class GamePage extends Page
{
    public function url($options = null): string
    {
        return url($this->slug());
    }
}
```

### Date Derivation

| IGDB data | Year | Month | Path |
|-----------|------|-------|------|
| Full date: `2023-09-27` | `2023` | `09` | `content/games/2023/09/{slug}/` |
| Year only: `2023` | `2023` | `00` | `content/games/2023/00/{slug}/` |
| No date | `00` | `00` | `content/games/00/00/{slug}/` |
| Future date | actual | actual | `content/games/{future-year}/{month}/{slug}/` |

### Media

Media files remain co-located with content — no structural change beyond the nesting. Kirby's thumbnail engine writes to `media/pages/games/{year}/{month}/{slug}/{hash}/`, inheriting sharding automatically.

### Panel Experience

- **Page tree:** Games appear under `Games > Year > Month > Slug`. Three levels of containers (no `.txt` files at year/month level, so they're pure navigation containers).
- **Search:** Panel search (Ctrl+K / Cmd+K) bypasses the tree — type game title, jump directly.
- **Lazy loading:** Kirby loads children via AJAX pagination. Each directory has at most ~5K entries (worst case), well within Kirby's page limit defaults.

### Import Pipeline Changes

Current: write to `content/games/{slug}/`
New: write to `content/games/{year}/{month}/{slug}/` + update `data/games/index.php`

```php
// Pseudocode
$date = $igdbData['release_date'];
$year = $date ? date('Y', strtotime($date)) : '00';
$month = $date ? date('m', strtotime($date)) : '00';
$slug = slugify($igdbData['title']);

// Write content
$path = "content/games/{$year}/{$month}/{$slug}";
mkdir($path, 0777, true);
file_put_contents("$path/game.txt", $content);

// Update index atomically
$index = include 'data/games/index.php';
$index[$slug] = "$year/$month";
file_put_contents('data/games/index.tmp.php', '<?php return ' . var_export($index, true) . ';');
rename('data/games/index.tmp.php', 'data/games/index.php');
```

## Migration of Existing Content

15 existing games in `content/games/{slug}/` need to be moved:

1. Read each `game.txt`, extract `ReleaseDate` field
2. Derive year/month using the same rules as above
3. Move directory to `content/games/{year}/{month}/{slug}/`
4. Build initial `data/games/index.php`

A one-shot migration script handles this.

## Edge Cases

| Scenario | Behavior |
|----------|----------|
| Duplicate slugs (two games with same title) | Append IGDB ID suffix: `{slug}-242408` |
| Index missing entry (race condition / corruption) | Route falls through to `next()` → 404 |
| Panel: editor moves a game via Panel | Index must be updated or refreshed on next import |
| Year/month directories accumulate old empty dirs | Harmless — empty directories cost nothing |
| Slug collides with system page (e.g. "search", "home") | Route checks `page($slug)` first, skips if system page exists |
| `data/` directory doesn't exist yet | Created on first import or during migration |

## Success Metrics

- No single directory exceeds ~5K entries (worst-case month for a popular year)
- Page load time remains under 200ms for game detail pages
- `data/games/index.php` lookup stays sub-millisecond (opcached)
- Panel search returns results within 2 seconds for 100K games
- Git operations on content repo remain viable (no single directory with 100K+ items)
