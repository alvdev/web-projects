# Game Page Hero Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the full-width background-image hero on game detail pages with a 3-column grid (cover, video/screenshot, stats).

**Architecture:** Add `videos` and `websites` fields to IGDB API queries and import pipeline; add model methods to transform IGDB data into display formats; redesign the template into a responsive grid layout; update the Kirby blueprint for panel visibility.

**Tech Stack:** PHP 8, Kirby CMS 3, IGDB API, Tailwind CSS 4

---

### Task 1: Add videos+websites to IGDB queries

**Files:**
- Modify: `site/igdb/IGDBClient.php` (lines ~96-114)

- [ ] **Step 1: Add `videos`, `websites` to `fetchGameById` fields**

```php
// In fetchGameById(), change the fields string to include videos and websites
$result = $this->post('games', 'fields name,slug,summary,first_release_date,cover,screenshots,videos,websites.category,websites.url,genres,themes,involved_companies.company.name,involved_companies.developer,involved_companies.publisher,platforms,rating,aggregated_rating; where id = ' . $id . ';');
```

- [ ] **Step 2: Add `videos`, `websites` to `fetchGameBySlug` fields**

```php
// Same change as fetchGameById
$result = $this->post('games', 'fields name,slug,summary,first_release_date,cover,screenshots,videos,websites.category,websites.url,genres,themes,involved_companies.company.name,involved_companies.developer,involved_companies.publisher,platforms,rating,aggregated_rating; where slug = "' . $slug . '";');
```

- [ ] **Step 3: Add `videos`, `websites` to `fetchGames` call in `AutoFetcher`**

In `site/igdb/AutoFetcher.php` line ~27-28, add `videos`, `websites` to the fields array:
```php
$games = $this->client->fetchGames(
    ['name', 'slug', 'summary', 'first_release_date', 'cover', 'screenshots',
     'videos', 'websites.category', 'websites.url',
     'genres', 'themes', 'involved_companies', 'platforms', 'rating', 'aggregated_rating'],
    $limit,
    $offset,
    '',
    'rating desc'
);
```

- [ ] **Step 4: Add `videos`, `websites` to `searchGames` fields**

```php
return $this->post('games', "search \"{$safe}\"; fields name,slug,first_release_date,cover,screenshots,videos,websites.category,websites.url,genres.name,involved_companies.company.name,platforms.name,rating,summary; where version_parent = null; limit 20;");
```

- [ ] **Step 5: Verify syntax**

```bash
php -l site/igdb/IGDBClient.php && php -l site/igdb/AutoFetcher.php
```

---

### Task 2: Import videos+websites in GameImporter

**Files:**
- Modify: `site/igdb/GameImporter.php`

- [ ] **Step 1: Add Videos and Websites to the game.txt content in `import()`**

After the Screenshots line in the content string (around line 130), add:
```php
$videoStr = '';
$videoIds = $gameData['videos'] ?? [];
if (!empty($videoIds)) {
    $ids = array_column($videoIds, 'video_id');
    $videoStr = implode(', ', $ids);
}

$websiteStr = '';
$websiteData = $gameData['websites'] ?? [];
if (!empty($websiteData)) {
    $pairs = [];
    foreach ($websiteData as $w) {
        if (!empty($w['url'])) {
            $cat = $w['category'] ?? 0;
            $pairs[] = $cat . ':' . $w['url'];
        }
    }
    $websiteStr = implode(', ', $pairs);
}
```

Then update the content string to include `Videos: {$videoStr}` and `Websites: {$websiteStr}`:
```php
$content = "Title: {$name}\n\n----\n\nTemplate: game\n\n----\n\nSummary: {$summary}\n\n----\n\nReleaseDate: {$releaseDate}\n\n----\n\nDeveloper: {$developer}\n\n----\n\nPublisher: {$publisher}\n\n----\n\nGenres: {$genreNames}\n\n----\n\nTags: {$tagNames}\n\n----\n\nPlatforms: {$platformNames}\n\n----\n\nIgdbId: {$gameData['id']}\n\n----\n\nRating: {$rating}\n\n----\n\nAggregatedRating: {$aggRating}\n\n----\n\nFeatured:\n\n----\n\nScreenshots: {$screenshotStr}\n\n----\n\nVideos: {$videoStr}\n\n----\n\nWebsites: {$websiteStr}\n\n----\n";
```

- [ ] **Step 2: Add videos+websites to `importMissingMedia()`**

In `importMissingMedia()`, after the screenshots update block, add:
```php
if ($gameContent !== false && preg_match('/^Videos:\s*$/m', $gameContent)) {
    $videoIds = $gameData['videos'] ?? [];
    if (!empty($videoIds)) {
        $ids = array_column($videoIds, 'video_id');
        $newValue = implode(', ', $ids);
        $gameContent = preg_replace('/^Videos:.*$/m', "Videos: {$newValue}", $gameContent);
        file_put_contents($gameTxtPath, $gameContent);
        echo "  updated videos for {$slug}\n";
    }
}

if ($gameContent !== false && preg_match('/^Websites:\s*$/m', $gameContent)) {
    $websiteData = $gameData['websites'] ?? [];
    if (!empty($websiteData)) {
        $pairs = [];
        foreach ($websiteData as $w) {
            if (!empty($w['url'])) {
                $cat = $w['category'] ?? 0;
                $pairs[] = $cat . ':' . $w['url'];
            }
        }
        $newValue = implode(', ', $pairs);
        $gameContent = preg_replace('/^Websites:.*$/m', "Websites: {$newValue}", $gameContent);
        file_put_contents($gameTxtPath, $gameContent);
        echo "  updated websites for {$slug}\n";
    }
}
```

- [ ] **Step 3: Verify syntax**

```bash
php -l site/igdb/GameImporter.php
```

---

### Task 3: Add model methods

**Files:**
- Modify: `site/models/game.php`

- [ ] **Step 1: Add `videos()` method**

```php
public function videos(): array
{
    $raw = $this->content()->get('Videos')->value() ?? '';
    if (empty(trim($raw))) return [];
    return array_map(function ($id) {
        $id = trim($id);
        return 'https://www.youtube.com/embed/' . $id;
    }, explode(',', $raw));
}
```

- [ ] **Step 2: Add `websites()` method with category mapping**

```php
public function websites(): array
{
    $raw = $this->content()->get('Websites')->value() ?? '';
    if (empty(trim($raw))) return [];

    $labels = [
        1 => 'Official Website',
        13 => 'Steam',
        16 => 'Epic Games',
        17 => 'GOG',
        14 => 'Reddit',
        18 => 'Discord',
        6 => 'Twitch',
        5 => 'Twitter / X',
        8 => 'Instagram',
        9 => 'YouTube',
        10 => 'App Store (iOS)',
        12 => 'Google Play',
        20 => 'Google Play',
        21 => 'App Store (iOS)',
    ];

    return array_map(function ($entry) use ($labels) {
        $entry = trim($entry);
        $parts = explode(':', $entry, 2);
        if (count($parts) !== 2) {
            return ['label' => 'Website', 'url' => $entry];
        }
        $cat = (int) $parts[0];
        $url = $parts[1];
        return [
            'label' => $labels[$cat] ?? 'Website',
            'url' => $url,
        ];
    }, explode(',', $raw));
}
```

- [ ] **Step 3: Verify syntax**

```bash
php -l site/models/game.php
```

---

### Task 4: Redesign the game detail template

**Files:**
- Modify: `site/templates/game.php`

- [ ] **Step 1: Replace the hero section with the new grid layout**

Replace the entire content of `site/templates/game.php` with:

```php
<?php snippet('header') ?>

<h1 class="text-3xl font-bold text-text mb-6"><?= $page->title() ?></h1>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-8">
    <?php if ($cover = $page->cover()): ?>
    <div class="lg:col-span-1">
        <img src="<?= $cover->url() ?>" alt="<?= $page->title() ?>" class="w-full rounded-lg">
    </div>
    <?php endif ?>

    <div class="lg:col-span-2">
        <?php $videos = $page->videos() ?>
        <?php if (!empty($videos)): ?>
        <div class="aspect-video rounded-lg overflow-hidden bg-surface-alt">
            <iframe src="<?= $videos[0] ?>" class="w-full h-full" allowfullscreen loading="lazy"></iframe>
        </div>
        <?php elseif ($hero = $page->hero()): ?>
        <img src="<?= $hero->url() ?>" alt="<?= $page->title() ?>" class="w-full rounded-lg">
        <?php endif ?>
    </div>

    <div class="lg:col-span-1 space-y-4 text-sm">
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

        <?php if ($page->rating()->isNotEmpty()): ?>
        <div>
            <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Rating</h3>
            <p class="text-lg font-bold text-neon-magenta"><?= number_format((float)$page->rating(), 1) ?> / 100</p>
        </div>
        <?php endif ?>

        <?php if ($page->platforms()->isNotEmpty()): ?>
        <div>
            <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Platforms</h3>
            <p><?= $page->platforms() ?></p>
        </div>
        <?php endif ?>

        <?php if ($page->releaseDate()): ?>
        <div>
            <h3 class="text-xs uppercase tracking-wider text-muted mb-1">Released</h3>
            <p><?= $page->releaseDate() ?></p>
        </div>
        <?php endif ?>

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

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <div class="lg:col-span-2">
        <div class="text-sm text-muted mb-4">
            <?php if ($page->developer()->isNotEmpty()): ?><span><?= $page->developer() ?></span><?php endif ?>
            <?php if ($page->publisher()->isNotEmpty()): ?><span> • <?= $page->publisher() ?></span><?php endif ?>
        </div>
        <?php if ($page->summary()->isNotEmpty()): ?>
        <div class="text-text leading-relaxed mb-8">
            <?= $page->summary()->kt() ?>
        </div>
        <?php endif ?>
    </div>
</div>

<?php $shots = $page->screenshots() ?>
<?php if (!empty($shots)): ?>
<div class="mt-8 pt-8 border-t border-border">
    <h2 class="text-lg font-bold text-neon-green mb-6">Capturas</h2>
    <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
        <?php foreach ($shots as $shot): ?>
        <li>
            <a href="<?= $shot['full'] ?>" target="_blank" rel="noopener" class="block aspect-video rounded-lg overflow-hidden bg-surface-alt">
                <img src="<?= $shot['thumb'] ?>" alt="" loading="lazy" class="w-full h-full object-cover hover:opacity-80 transition">
            </a>
        </li>
        <?php endforeach ?>
    </ul>
</div>
<?php endif ?>

<?php $posts = $page->posts() ?>
<?php if ($posts->count() > 0): ?>
<div class="mt-8 pt-8 border-t border-border">
    <h2 class="text-lg font-bold text-neon-green mb-6">📰 Posts about <?= $page->title() ?></h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($posts as $post): ?>
            <?php snippet('post-card', ['post' => $post]) ?>
        <?php endforeach ?>
    </div>
</div>
<?php endif ?>

<?php snippet('footer') ?>
```

- [ ] **Step 2: Verify syntax**

```bash
php -l site/templates/game.php
```

---

### Task 5: Update Kirby blueprint

**Files:**
- Modify: `site/blueprints/pages/game.yml`

- [ ] **Step 1: Add read-only videos and websites fields**

Add after the `screenshots` field or in the appropriate section:
```yaml
  videos:
    label: Videos
    type: text
    readonly: true
  websites:
    label: Websites
    type: text
    readonly: true
```

---

### Task 6: Re-import a game to test

- [ ] **Step 1: Re-import arena-breakout-infinite to test video+website import**

```bash
php scripts/igdb.php import 298915
```

Expected output: `updated videos for arena-breakout-infinite` and/or `updated websites for arena-breakout-infinite`

- [ ] **Step 2: Verify the game.txt now has Videos and Websites fields**

```bash
grep -E '^(Videos:|Websites:)' content/games/arena-breakout-infinite/game.txt
```

- [ ] **Step 3: Visit the game page**

Check `/games/arena-breakout-infinite` — verify the grid layout, video embed, cover image, and stats column.
