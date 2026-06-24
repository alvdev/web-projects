# Game Detail Page Hero Redesign

## Overview

Replace the current full-width background-image hero on game detail pages with a responsive three-column grid layout featuring the game cover, a video (or screenshot fallback), and a stats panel.

## Motivation

The current hero uses the cover image as a stretched background (21:9) with text overlay — wastes visual space and doesn't showcase the game's trailer or stats effectively.

## Layout

```
Desktop (lg+):
┌─────────────────────────────────────────────────────┐
│                  Game Title (h1)                     │
├──────────┬─────────────────────────────┬─────────────┤
│          │                             │  GENRES     │
│  Cover   │  YouTube embed              │  Shooter    │
│  img     │  OR                          │  TAG        │
│  1/4     │  first screenshot (hero)    │  Tactico    │
│          │  2/4                        │             │
│          │                             │  RATING     │
│          │                             │  82.4/100   │
│          │                             │             │
│          │                             │  PLATFORMS  │
│          │                             │  PC, PS5    │
│          │                             │             │
│          │                             │  RELEASED   │
│          │                             │  2025-09-15 │
│          │                             │             │
│          │                             │  LINKS      │
│          │                             │  Steam ↗    │
│          │                             │  Official ↗ │
├──────────┴─────────────────────────────┴─────────────┤
│  Summary / Description                                │
├──────────────────────────────────────────────────────┤
│  Screenshots (unchanged)                              │
├──────────────────────────────────────────────────────┤
│  Posts (unchanged)                                    │
└──────────────────────────────────────────────────────┘

Mobile (<lg):
┌───────────────┐
│   Title       │
├───────────────┤
│   Cover       │
├───────────────┤
│   Video/Shot  │
├───────────────┤
│   Stats       │
├───────────────┤
│   Summary     │
├───────────────┤
│  Screenshots  │
└───────────────┘
```

## Files to Change

### 1. `site/igdb/IGDBClient.php`

Add `videos`, `websites` to the fields list in:
- `fetchGameById()` — line ~101
- `fetchGameBySlug()` — line ~108
- `fetchGames()` (used by AutoFetcher) — line ~96
- `searchGames()` — line ~114

IGDB returns:
- `videos`: `[{ "id": int, "video_id": "youtube-id", "name": "Trailer" }]`
- `websites`: `[{ "id": int, "url": "https://...", "category": int }]`

### 2. `site/igdb/GameImporter.php`

- In `import()`: add `Videos` and `Websites` fields to `game.txt` content
- In `importMissingMedia()`: also check and fill missing Videos/Websites fields

**Videos format in game.txt:** `Videos: youtubeId1, youtubeId2`
**Websites format in game.txt:** `Websites: category:url, category:url`

### 3. `site/models/game.php`

Add methods:
- `videos(): array` — returns array of YouTube embed URLs from stored video IDs
- `websites(): array` — returns array of `[label, url]` pairs, mapping IGDB website categories to readable labels:

| Category | Label |
|----------|-------|
| 1 | Official Website |
| 13 | Steam |
| 16 | Epic Games |
| 17 | GOG |
| 14 | Reddit |
| 18 | Discord |
| 6 | Twitch |
| 5 | Twitter |
| 8 | Instagram |
| 9 | YouTube |
| others | Website |

### 4. `site/templates/game.php`

Replace the current hero section:

```php
<?php snippet('header') ?>

<h1 class="text-3xl font-bold text-text mb-6"><?= $page->title() ?></h1>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
    <!-- Left: Cover -->
    <div class="lg:col-span-1">
        <?php if ($cover = $page->cover()): ?>
        <img src="<?= $cover->url() ?>" alt="<?= $page->title() ?>" class="w-full rounded-lg">
        <?php endif ?>
    </div>

    <!-- Center: Video or Hero screenshot -->
    <div class="lg:col-span-2">
        <?php $videos = $page->videos() ?>
        <?php if (!empty($videos)): ?>
        <div class="aspect-video rounded-lg overflow-hidden">
            <iframe src="<?= $videos[0] ?>" class="w-full h-full" allowfullscreen loading="lazy"></iframe>
        </div>
        <?php elseif ($hero = $page->hero()): ?>
        <img src="<?= $hero->url() ?>" alt="<?= $page->title() ?>" class="w-full rounded-lg">
        <?php endif ?>
    </div>

    <!-- Right: Stats -->
    <div class="lg:col-span-1 space-y-4 text-sm">
        <!-- Genres -->
        <?php $genres = $page->genreList() ?>
        <?php if (!empty($genres)): ?>
        <div>
            <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Genres</h3>
            <div class="flex flex-wrap gap-1">
                <?php foreach ($genres as $g): ?>
                <span class="px-2 py-0.5 rounded border border-neon-cyan/30 text-neon-cyan text-xs"><?= $g ?></span>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>

        <!-- Tags -->
        <?php $tags = $page->tagList() ?>
        <?php if (!empty($tags)): ?>
        <div>
            <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Tags</h3>
            <div class="flex flex-wrap gap-1">
                <?php foreach ($tags as $t): ?>
                <span class="px-2 py-0.5 rounded border border-neon-green/20 text-neon-green text-xs"><?= $t ?></span>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>

        <!-- Rating -->
        <?php if ($page->rating()->isNotEmpty()): ?>
        <div>
            <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Rating</h3>
            <p class="text-lg font-bold text-neon-magenta"><?= number_format((float)$page->rating(), 1) ?> / 100</p>
        </div>
        <?php endif ?>

        <!-- Platforms -->
        <?php if ($page->platforms()->isNotEmpty()): ?>
        <div>
            <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Platforms</h3>
            <p><?= $page->platforms() ?></p>
        </div>
        <?php endif ?>

        <!-- Release Date -->
        <?php if ($page->releaseDate()): ?>
        <div>
            <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Released</h3>
            <p><?= $page->releaseDate() ?></p>
        </div>
        <?php endif ?>

        <!-- Links -->
        <?php $links = $page->websites() ?>
        <?php if (!empty($links)): ?>
        <div>
            <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Links</h3>
            <ul class="space-y-1">
                <?php foreach ($links as $link): ?>
                <li><a href="<?= $link['url'] ?>" target="_blank" rel="noopener" class="text-neon-cyan hover:underline"><?= $link['label'] ?> ↗</a></li>
                <?php endforeach ?>
            </ul>
        </div>
        <?php endif ?>
    </div>
</div>

<!-- Summary, Screenshots, Posts — unchanged from current template -->
```

### 5. `site/blueprints/pages/game.yml`

Add read-only fields:
```yaml
fields:
  videos:
    label: Videos
    type: text
    readonly: true
  websites:
    label: Websites
    type: text
    readonly: true
```

## Edge Cases

| Case | Behavior |
|------|----------|
| No video, no hero | Center column shows nothing (empty space) |
| No cover | Left column empty |
| No stats data | Each stat section hides itself if empty |
| No video, hero exists | Center shows hero/screenshot image |
| Mobile | Stacks vertically: cover → video/hero → stats |

## Verification

1. Visit `/games/animal-company` — verify grid layout, cover, video/hero, stats
2. Visit `/games/animal-company` on mobile width — verify stacked layout
3. Visit a game without video — verify screenshot fallback
4. Visit a game without cover — verify empty left column
5. `php -l` all changed PHP files
