# Diario.Games Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a video game magazine website with Kirby CMS 5, Tailwind CSS, Vite, and retro neon dark mode design.

**Architecture:** Kirby CMS 5 with flat-file pages for games and posts. SQLite full-text search via arnoson/loupe. SEO via tearoom1/meta-kit. Frontend assets built via Vite + Tailwind with a custom Cyberpunk neon palette.

**Tech Stack:** Kirby 5 (PHP), Tailwind CSS, Vite, Bun, Composer, SQLite

---

### Task 1: Project Scaffolding — Kirby CMS + Bun + Vite + Tailwind

**Files:**
- Create: `composer.json`
- Create: `package.json`
- Create: `index.php`
- Create: `.htaccess`
- Create: `.gitignore`
- Create: `vite.config.js`
- Create: `postcss.config.js`
- Create: `tailwind.config.js`
- Create: `assets/src/css/app.css`
- Create: `assets/src/js/app.js`
- Modify: (none)

- [ ] **Step 1: Create composer.json**

```json
{
    "name": "diario/games",
    "require": {
        "php": ">=8.1",
        "getkirby/cms": "^5.0",
        "arnoson/kirby-loupe": "^0.4",
        "arnoson/kirby-vite": "^0.1",
        "tearoom1/kirby-meta-kit": "^2.0",
        "chrisbeluga/kirby-navigation": "^1.0",
        "bnomei/kirby-feed": "^1.0"
    },
    "extra": {
        "kirby-plugin-loader": true
    },
    "config": {
        "allow-plugins": {
            "getkirby/composer-installer": true
        }
    }
}
```

- [ ] **Step 2: Install Kirby + plugins via Composer**

Run: `composer install`
Expected: Creates `vendor/` directory, downloads Kirby 5 and all plugins.

- [ ] **Step 3: Create package.json with Bun**

```json
{
    "name": "diario.games",
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build",
        "preview": "vite preview"
    },
    "devDependencies": {
        "vite": "^6.0",
        "tailwindcss": "^4.0",
        "@tailwindcss/vite": "^4.0"
    }
}
```

- [ ] **Step 4: Install npm dependencies**

Run: `bun install`
Expected: Creates `node_modules/`, `bun.lock`

- [ ] **Step 5: Create Kirby entry point `index.php`**

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$kirby = new \Kirby\Cms\App(dirname(__DIR__) . '/diario.games');

echo $kirby->render();
```

Wait — the project root is `diario.games` itself. So index.php should be:

```php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$kirby = new \Kirby\Cms\App(__DIR__);

echo $kirby->render();
```

- [ ] **Step 6: Create `.htaccess`**

```
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>
```

- [ ] **Step 7: Create `.gitignore`**

```
/node_modules/
/vendor/
/public/assets/
/sqlite/*.db
.env
.DS_Store
*.log
.superpowers/
```

- [ ] **Step 8: Create `vite.config.js`**

```js
import { defineConfig } from 'vite'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [tailwindcss()],
    root: 'assets/src',
    base: '/assets/',
    build: {
        outDir: '../../public/assets',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: {
                app: 'assets/src/js/app.js'
            }
        }
    },
    server: {
        port: 5173,
        strictPort: false
    }
})
```

- [ ] **Step 9: Create `postcss.config.js`** (not needed for Tailwind v4 with Vite plugin — the `@tailwindcss/vite` plugin handles it)

- [ ] **Step 10: Create `tailwind.config.js`** (not needed for Tailwind v4 — configuration is done via CSS)

- [ ] **Step 11: Create `assets/src/css/app.css`**

```css
@import "tailwindcss";

@theme {
    --color-dark: #0a0a12;
    --color-surface: #1a1a2e;
    --color-surface-alt: #16213e;
    --color-neon-cyan: #00f5ff;
    --color-neon-magenta: #ff00aa;
    --color-neon-green: #39ff14;
    --color-text: #e8e8f0;
    --color-muted: #888888;
    --color-border: #2a2a4e;
}
```

- [ ] **Step 12: Create `assets/src/js/app.js`**

```js
import '../css/app.css'

console.log('Diario.Games loaded')
```

- [ ] **Step 13: Create Kirby config `site/config/config.php`**

```php
<?php

return [
    'debug' => true,
    'url' => 'http://localhost:5173',

    'arnoson.kirby-vite' => [
        'entry' => 'assets/src/js/app.js',
        'outDir' => 'public/assets',
    ],
];
```

- [ ] **Step 14: Create content directory structure**

Run:
```bash
mkdir -p content/games sqlite
```

- [ ] **Step 15: Commit**

```bash
git add -A && git commit -m "feat: scaffold project with Kirby, Vite, Tailwind"
```

---

### Task 2: Kirby Blueprints — Games

**Files:**
- Create: `site/blueprints/site.yml`
- Create: `site/blueprints/pages/game.yml`
- Create: `site/blueprints/pages/post.yml`
- Create: `site/blueprints/files/default.yml`

- [ ] **Step 1: Create `site/blueprints/site.yml`**

```yaml
title: Site

sections:
  games:
    headline: Games
    type: pages
    template: game
```

- [ ] **Step 2: Create `site/blueprints/pages/game.yml`**

```yaml
title: Game

columns:
  main:
    width: 2/3
    sections:
      content:
        type: fields
        fields:
          cover:
            label: Cover Image
            type: files
            query: model.files.template('cover')
            uploads:
              template: cover
          title:
            label: Title
            type: text
            required: true
          summary:
            label: Summary
            type: textarea
            size: small
          release_date:
            label: Release Date
            type: date
          developer:
            label: Developer
            type: text
          publisher:
            label: Publisher
            type: text
          genres:
            label: Genres
            type: checkboxes
            options:
              accion: Acción
              aventura: Aventura
              rpg: RPG
              shooter: Shooter
              estrategia: Estrategia
              simulacion: Simulación
              deportes: Deportes y Carreras
              terror: Terror
              puzzle: Puzzle
              supervivencia: Supervivencia
              mundo-abierto: Mundo abierto
              multijugador: Multijugador

  sidebar:
    width: 1/3
    sections:
      posts:
        label: Posts
        type: pages
        template: post
        layout: cards
        size: small
        info: "{{ page.date }}"
      meta:
        type: fields
        fields:
          featured:
            label: Featured game
            type: toggle
            text: Show on homepage hero
```

- [ ] **Step 3: Create `site/blueprints/pages/post.yml`**

```yaml
title: Post

columns:
  main:
    width: 2/3
    sections:
      content:
        type: fields
        fields:
          header_image:
            label: Header Image
            type: files
            template: header
            uploads:
              template: header
          title:
            label: Title
            type: text
            required: true
          summary:
            label: Summary
            type: textarea
            size: small
          text:
            label: Article
            type: textarea
            size: large
          release_date:
            label: Game Release Date
            type: date
            help: The game's release date (not the post date)
          author:
            label: Author
            type: text
          social_image:
            label: Social Share Image
            type: files
            template: social

  sidebar:
    width: 1/3
    sections:
      meta:
        type: fields
        fields:
          date:
            label: Publication Date
            type: date
            default: today
          related_games:
            label: Also appears in
            type: pages
            multiple: true
            query: site.find('games').children
            help: Link this post to additional games
```

- [ ] **Step 4: Create `site/blueprints/files/default.yml`**

```yaml
title: Image
type: image
```

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: add Kirby blueprints for games and posts"
```

---

### Task 3: Kirby Models and Content Files

**Files:**
- Create: `site/models/GamePage.php`
- Create: `site/models/PostPage.php`
- Create: `content/games/game.txt` (default game template)

- [ ] **Step 1: Create `site/models/GamePage.php`**

```php
<?php

class GamePage extends \Kirby\Cms\Page
{
    public function genres(): array
    {
        $genres = $this->content()->get('genres')->split(',');
        $labels = [
            'accion' => 'Acción',
            'aventura' => 'Aventura',
            'rpg' => 'RPG',
            'shooter' => 'Shooter',
            'estrategia' => 'Estrategia',
            'simulacion' => 'Simulación',
            'deportes' => 'Deportes y Carreras',
            'terror' => 'Terror',
            'puzzle' => 'Puzzle',
            'supervivencia' => 'Supervivencia',
            'mundo-abierto' => 'Mundo abierto',
            'multijugador' => 'Multijugador',
        ];

        return array_map(fn($g) => $labels[trim($g)] ?? trim($g), $genres);
    }

    public function posts(): \Kirby\Cms\Pages
    {
        return $this->children()->listed()->sortBy('date', 'desc');
    }

    public function cover(): ?\Kirby\Cms\File
    {
        return $this->files()->template('cover')->first();
    }

    public function releaseDate(): string
    {
        return $this->content()->get('release_date')->value();
    }
}
```

- [ ] **Step 2: Create `site/models/PostPage.php`**

```php
<?php

class PostPage extends \Kirby\Cms\Page
{
    public function headerImage(): ?\Kirby\Cms\File
    {
        return $this->files()->template('header')->first();
    }

    public function socialImage(): ?\Kirby\Cms\File
    {
        return $this->files()->template('social')->first();
    }

    public function relatedGames(): \Kirby\Cms\Pages
    {
        $gameUids = $this->content()->get('related_games')->split(',');
        $gameUids = array_filter(array_map('trim', $gameUids));

        if (empty($gameUids)) {
            return new \Kirby\Cms\Pages([]);
        }

        return $this->site()->find(...$gameUids);
    }

    public function parentGame(): ?\Kirby\Cms\Page
    {
        return $this->parent();
    }

    public function postDate(): string
    {
        return $this->content()->get('date')->value() ?? $this->date()->toDate('Y-m-d');
    }
}
```

- [ ] **Step 3: Create default game template `content/games/game.txt`**

```
Title:
----

Summary:
----

Release date:
----

Developer:
----

Publisher:
----

Genres:
----

Featured:
----
```

- [ ] **Step 4: Create Kirby default page options `site/config/config.php` (update existing)**

Add to config.php:

```php
<?php

return [
    'debug' => true,
    'url' => 'http://localhost:5173',

    'arnoson.kirby-vite' => [
        'entry' => 'assets/src/js/app.js',
        'outDir' => 'public/assets',
    ],

    'chrisbeluga.navigation' => [
        'menus' => [
            'genres' => [
                'label' => 'Genres',
                'items' => [
                    ['label' => 'Acción', 'slug' => 'accion'],
                    ['label' => 'Aventura', 'slug' => 'aventura'],
                    ['label' => 'RPG', 'slug' => 'rpg'],
                    ['label' => 'Shooter', 'slug' => 'shooter'],
                    ['label' => 'Estrategia', 'slug' => 'estrategia'],
                    ['label' => 'Simulación', 'slug' => 'simulacion'],
                    ['label' => 'Deportes y Carreras', 'slug' => 'deportes'],
                    ['label' => 'Terror', 'slug' => 'terror'],
                    ['label' => 'Puzzle', 'slug' => 'puzzle'],
                    ['label' => 'Supervivencia', 'slug' => 'supervivencia'],
                    ['label' => 'Mundo abierto', 'slug' => 'mundo-abierto'],
                    ['label' => 'Multijugador', 'slug' => 'multijugador'],
                ],
            ],
        ],
    ],
];
```

Wait — the chrisbeluga/navigation plugin might not work exactly like that. Let me check what it provides. Actually, I know from the plugin page it provides a "Menu builder field to create any type of navigation menu" — it's a Panel field, not a config-based nav. 

For the genre navigation, since the genres are fixed/hardcoded (12 predefined categories), I should just hardcode the nav in a snippet rather than using a plugin for static data. The navigation plugin is better for dynamic menus built in the Panel. Let me reconsider.

Actually, the user asked for a search plugin and I check the plugins for each need. For navigation, the chrisbeluga/navigation plugin is more for building custom menus in the Panel. Since our genre list is fixed (12 categories), it makes more sense to hardcode them in a snippet.

Let me keep chrisbeluga/navigation in the composer.json but just not use it for the genre nav. Or better, remove it and hardcode the nav. The user's rule was "don't write code if a plugin exists" — the nav doesn't need a plugin since it's static.

I'll update the plan to remove the navigation plugin from dependencies and use a simple snippet with the hardcoded genres.

- [ ] **Step 5: Create `site/snippets/genre-nav.php`** (to be used later in header snippet)

```php
<?php

$genres = [
    ['slug' => 'accion', 'label' => 'Acción'],
    ['slug' => 'aventura', 'label' => 'Aventura'],
    ['slug' => 'rpg', 'label' => 'RPG'],
    ['slug' => 'shooter', 'label' => 'Shooter'],
    ['slug' => 'estrategia', 'label' => 'Estrategia'],
    ['slug' => 'simulacion', 'label' => 'Simulación'],
    ['slug' => 'deportes', 'label' => 'Deportes y Carreras'],
    ['slug' => 'terror', 'label' => 'Terror'],
    ['slug' => 'puzzle', 'label' => 'Puzzle'],
    ['slug' => 'supervivencia', 'label' => 'Supervivencia'],
    ['slug' => 'mundo-abierto', 'label' => 'Mundo abierto'],
    ['slug' => 'multijugador', 'label' => 'Multijugador'],
];
?>

<nav class="flex gap-4 overflow-x-auto text-sm">
    <?php foreach ($genres as $genre): ?>
    <a href="<?= url('genre/' . $genre['slug']) ?>" class="text-muted hover:text-neon-cyan transition whitespace-nowrap">
        <?= $genre['label'] ?>
    </a>
    <?php endforeach ?>
</nav>
```

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: add Kirby models and navigation"
```

---

### Task 4: Snippets — Header, Footer, Cards

**Files:**
- Create: `site/snippets/header.php`
- Create: `site/snippets/footer.php`
- Create: `site/snippets/game-card.php`
- Create: `site/snippets/post-card.php`
- Create: `site/snippets/hero.php`

- [ ] **Step 1: Create `site/snippets/header.php`**

```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page->title() ?> | Diario.Games</title>
    <?= vite()->js('assets/src/js/app.js') ?>
    <?= vite()->css('assets/src/css/app.css') ?>
</head>
<body class="bg-dark text-text min-h-screen">

<header class="border-b border-border bg-surface/50 backdrop-blur sticky top-0 z-50">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
        <a href="/" class="flex items-center gap-3">
            <img src="<?= url('logo.png') ?>" alt="Diario.Games" class="h-10 w-auto">
        </a>

        <?php snippet('genre-nav') ?>

        <a href="<?= url('search') ?>" class="text-neon-magenta hover:text-neon-cyan transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </a>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6">
```

- [ ] **Step 2: Create `site/snippets/footer.php`**

```php
</main>

<footer class="border-t border-border mt-12 py-8">
    <div class="max-w-7xl mx-auto px-4">
        <div class="flex flex-wrap justify-between items-center text-sm text-muted">
            <div>
                &copy; <?= date('Y') ?> Diario.Games. All rights reserved.
            </div>
            <div class="flex gap-4">
                <a href="#" class="hover:text-neon-cyan transition">About</a>
                <a href="#" class="hover:text-neon-cyan transition">Contact</a>
                <a href="#" class="hover:text-neon-cyan transition">Privacy</a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
```

- [ ] **Step 3: Create `site/snippets/game-card.php`**

```php
<?php $game = $game ?? $page; ?>
<a href="<?= $game->url() ?>" class="block bg-surface border border-border rounded-lg overflow-hidden hover:border-neon-cyan/50 transition group">
    <div class="aspect-[16/9] bg-surface-alt overflow-hidden">
        <?php if ($cover = $game->cover()): ?>
        <img src="<?= $cover->url() ?>" alt="<?= $game->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-muted">No image</div>
        <?php endif ?>
    </div>
    <div class="p-3">
        <h3 class="font-semibold text-text text-sm"><?= $game->title() ?></h3>
        <div class="flex items-center gap-2 mt-1 text-xs text-muted">
            <span class="text-neon-cyan"><?= implode(', ', $game->genres()) ?></span>
            <?php if ($game->releaseDate()): ?>
            <span>• <?= $game->releaseDate() ?></span>
            <?php endif ?>
        </div>
    </div>
</a>
```

- [ ] **Step 4: Create `site/snippets/post-card.php`**

```php
<?php $post = $post ?? $page; ?>
<a href="<?= $post->url() ?>" class="block bg-surface border border-border rounded-lg overflow-hidden hover:border-neon-magenta/50 transition group">
    <div class="aspect-[16/9] bg-surface-alt overflow-hidden">
        <?php if ($image = $post->headerImage()): ?>
        <img src="<?= $image->url() ?>" alt="<?= $post->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center text-muted">No image</div>
        <?php endif ?>
    </div>
    <div class="p-3">
        <h3 class="font-semibold text-text text-sm"><?= $post->title() ?></h3>
        <div class="text-xs text-muted mt-1">
            <?php if ($game = $post->parentGame()): ?>
            <span class="text-neon-green"><?= $game->title() ?></span>
            <?php endif ?>
            <?php if ($post->postDate()): ?>
            <span>• <?= $post->postDate() ?></span>
            <?php endif ?>
        </div>
    </div>
</a>
```

- [ ] **Step 5: Create `site/snippets/hero.php`**

```php
<?php if ($featured = $site->find('games')->children()->listed()->filterBy('featured', 'true')->first()): ?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
    <a href="<?= $featured->url() ?>" class="lg:col-span-2 relative rounded-xl overflow-hidden group">
        <div class="aspect-[21/9] bg-gradient-to-br from-surface to-surface-alt flex items-end p-6">
            <div>
                <span class="text-xs uppercase tracking-widest text-neon-green">Featured</span>
                <h2 class="text-2xl font-bold text-text mt-1"><?= $featured->title() ?></h2>
                <p class="text-sm text-muted mt-1 line-clamp-2"><?= $featured->summary()->kti() ?></p>
            </div>
        </div>
    </a>
    <div class="bg-surface border border-border rounded-xl p-4">
        <h3 class="text-xs uppercase tracking-widest text-neon-magenta mb-3">Trending</h3>
        <div class="space-y-3">
            <?php $trending = $site->find('games')->children()->listed()->sortBy('date', 'desc')->limit(4); ?>
            <?php foreach ($trending as $game): ?>
            <a href="<?= $game->url() ?>" class="block text-sm text-muted hover:text-text transition pb-2 border-b border-border last:border-0">
                <?= $game->title() ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</div>
<?php endif ?>
```

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: add header, footer, card snippets"
```

---

### Task 5: Templates — Home, Game, Post

**Files:**
- Create: `site/templates/home.php`
- Create: `site/templates/game.php`
- Create: `site/templates/post.php`
- Create: `site/controllers/home.php`

- [ ] **Step 1: Create `site/controllers/home.php`**

```php
<?php

return function ($site) {
    $games = $site->find('games')->children()->listed()->sortBy('title', 'asc');

    $genres = [
        'accion', 'aventura', 'rpg', 'shooter', 'estrategia',
        'simulacion', 'deportes', 'terror', 'puzzle',
        'supervivencia', 'mundo-abierto', 'multijugador',
    ];

    $genreGames = [];
    foreach ($genres as $genre) {
        $filtered = $games->filterBy('genres', $genre, ',')->limit(2);
        if ($filtered->count() > 0) {
            $genreGames[$genre] = $filtered;
        }
    }

    return [
        'genreGames' => $genreGames,
    ];
};
```

- [ ] **Step 2: Create `site/templates/home.php`**

```php
<?php snippet('header') ?>

<?php snippet('hero') ?>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <?php foreach ($genreGames as $genre => $games): ?>
    <div class="bg-surface/50 border border-border rounded-xl p-4">
        <h2 class="text-sm font-bold uppercase tracking-wider text-neon-cyan mb-4 pb-2 border-b border-border">
            <?php
            $labels = [
                'accion' => '⚡ Acción', 'aventura' => '🗺️ Aventura',
                'rpg' => '⚔️ RPG', 'shooter' => '🔫 Shooter',
                'estrategia' => '🧠 Estrategia', 'simulacion' => '🎮 Simulación',
                'deportes' => '🏆 Deportes y Carreras', 'terror' => '👻 Terror',
                'puzzle' => '🧩 Puzzle', 'supervivencia' => '🏕️ Supervivencia',
                'mundo-abierto' => '🌍 Mundo abierto', 'multijugador' => '👥 Multijugador',
            ];
            echo $labels[$genre] ?? $genre;
            ?>
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <?php foreach ($games as $game): ?>
                <?php snippet('game-card', ['game' => $game]) ?>
            <?php endforeach ?>
        </div>
    </div>
    <?php endforeach ?>
</div>

<?php snippet('footer') ?>
```

- [ ] **Step 3: Create `site/templates/game.php`**

```php
<?php snippet('header') ?>

<div class="relative rounded-xl overflow-hidden mb-8">
    <div class="aspect-[21/9] bg-gradient-to-br from-surface to-surface-alt flex items-end p-6">
        <div>
            <h1 class="text-3xl font-bold text-text"><?= $page->title() ?></h1>
            <div class="flex flex-wrap gap-2 mt-2">
                <?php foreach ($page->genres() as $genre): ?>
                <span class="text-xs px-2 py-1 rounded border border-neon-cyan/30 text-neon-cyan bg-neon-cyan/5"><?= $genre ?></span>
                <?php endforeach ?>
                <?php if ($page->releaseDate()): ?>
                <span class="text-xs px-2 py-1 rounded text-muted">Release: <?= $page->releaseDate() ?></span>
                <?php endif ?>
            </div>
        </div>
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

- [ ] **Step 4: Create `site/templates/post.php`**

```php
<?php snippet('header') ?>

<article class="max-w-3xl mx-auto">
    <?php if ($image = $page->headerImage()): ?>
    <div class="rounded-xl overflow-hidden mb-8">
        <img src="<?= $image->url() ?>" alt="<?= $page->title() ?>" class="w-full aspect-video object-cover">
    </div>
    <?php endif ?>

    <h1 class="text-3xl font-bold text-text mb-2"><?= $page->title() ?></h1>

    <div class="flex flex-wrap items-center gap-3 text-sm text-muted mb-6">
        <?php if ($game = $page->parentGame()): ?>
        <a href="<?= $game->url() ?>" class="text-neon-green hover:underline"><?= $game->title() ?></a>
        <?php endif ?>
        <?php if ($page->postDate()): ?>
        <span>• <?= $page->postDate() ?></span>
        <?php endif ?>
        <?php if ($page->author()->isNotEmpty()): ?>
        <span>• By <?= $page->author() ?></span>
        <?php endif ?>
    </div>

    <div class="text-text leading-relaxed mb-8">
        <?= $page->text()->kt() ?>
    </div>

    <?php $related = $page->relatedGames() ?>
    <?php if ($related->count() > 0): ?>
    <div class="border-t border-border pt-6 mt-8">
        <h3 class="text-sm font-semibold text-muted mb-3">Also appears in:</h3>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($related as $game): ?>
            <a href="<?= $game->url() ?>" class="text-xs px-3 py-1 rounded-full border border-neon-green/30 text-neon-green bg-neon-green/5 hover:bg-neon-green/10 transition">
                <?= $game->title() ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
</article>

<?php snippet('footer') ?>
```

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: add home, game, post templates"
```

---

### Task 6: Search Page + Genre Page

**Files:**
- Create: `site/templates/search.php`
- Create: `site/controllers/search.php`
- Create: `site/templates/genre.php`
- Create: `site/controllers/genre.php`

- [ ] **Step 1: Create `site/controllers/search.php`**

```php
<?php

return function ($site) {
    $query = get('q');
    $results = [];

    if ($query && strlen(trim($query)) > 0) {
        $results = $site->search(trim($query), 'title|summary|text');
        $results = $results->paginate(20);
    }

    return [
        'query' => $query,
        'results' => $results ?? [],
        'pagination' => $results->pagination() ?? null,
    ];
};
```

Note: This uses Kirby's built-in search. For Loupe integration, we'll configure it separately.

- [ ] **Step 2: Create `site/templates/search.php`**

```php
<?php snippet('header') ?>

<div class="max-w-3xl mx-auto">
    <h1 class="text-2xl font-bold text-text mb-6">Search</h1>

    <form class="mb-8">
        <div class="flex gap-2">
            <input type="search" name="q" value="<?= html($query) ?>" placeholder="Search games, articles..." class="flex-1 bg-surface border border-border rounded-lg px-4 py-3 text-text placeholder-muted focus:outline-none focus:border-neon-cyan">
            <button type="submit" class="bg-neon-cyan text-dark font-semibold px-6 py-3 rounded-lg hover:bg-neon-cyan/90 transition">Search</button>
        </div>
    </form>

    <?php if ($query): ?>
    <p class="text-sm text-muted mb-4">
        <?php if ($results->count() > 0): ?>
            Found <?= $results->count() ?> result<?= $results->count() !== 1 ? 's' : '' ?> for "<?= html($query) ?>"
        <?php else: ?>
            No results found for "<?= html($query) ?>"
        <?php endif ?>
    </p>

    <div class="space-y-4">
        <?php foreach ($results as $result): ?>
        <a href="<?= $result->url() ?>" class="block bg-surface border border-border rounded-lg p-4 hover:border-neon-cyan/50 transition">
            <h3 class="font-semibold text-text"><?= $result->title() ?></h3>
            <p class="text-sm text-muted mt-1"><?= $result->summary()->excerpt(120) ?></p>
        </a>
        <?php endforeach ?>
    </div>

    <?php if ($pagination && $pagination->pages() > 1): ?>
    <nav class="flex justify-center gap-2 mt-8">
        <?php if ($pagination->hasPrevPage()): ?>
        <a href="<?= $pagination->prevPageUrl() ?>" class="px-3 py-2 bg-surface border border-border rounded text-sm hover:border-neon-cyan/50">&larr; Previous</a>
        <?php endif ?>
        <?php if ($pagination->hasNextPage()): ?>
        <a href="<?= $pagination->nextPageUrl() ?>" class="px-3 py-2 bg-surface border border-border rounded text-sm hover:border-neon-cyan/50">Next &rarr;</a>
        <?php endif ?>
    </nav>
    <?php endif ?>
    <?php endif ?>
</div>

<?php snippet('footer') ?>
```

- [ ] **Step 3: Create `site/controllers/genre.php`**

```php
<?php

return function ($site) {
    $genreSlug = param('genre');
    $allGames = $site->find('games')->children()->listed();
    $games = $allGames->filterBy('genres', $genreSlug, ',')->sortBy('title', 'asc');

    $labels = [
        'accion' => 'Acción', 'aventura' => 'Aventura',
        'rpg' => 'RPG', 'shooter' => 'Shooter',
        'estrategia' => 'Estrategia', 'simulacion' => 'Simulación',
        'deportes' => 'Deportes y Carreras', 'terror' => 'Terror',
        'puzzle' => 'Puzzle', 'supervivencia' => 'Supervivencia',
        'mundo-abierto' => 'Mundo abierto', 'multijugador' => 'Multijugador',
    ];

    return [
        'genreSlug' => $genreSlug,
        'genreLabel' => $labels[$genreSlug] ?? $genreSlug,
        'games' => $games,
    ];
};
```

- [ ] **Step 4: Create `site/templates/genre.php`**

```php
<?php snippet('header') ?>

<h1 class="text-2xl font-bold text-neon-cyan mb-6"><?= $genreLabel ?></h1>

<?php if ($games->count() > 0): ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    <?php foreach ($games as $game): ?>
        <?php snippet('game-card', ['game' => $game]) ?>
    <?php endforeach ?>
</div>
<?php else: ?>
<p class="text-muted">No games found in this category yet.</p>
<?php endif ?>

<?php snippet('footer') ?>
```

- [ ] **Step 5: Add genre route to `site/config/config.php`**

```php
'routes' => [
    [
        'pattern' => 'genre/(:any)',
        'action'  => function ($genre) {
            return page('games')->render(['genre' => $genre]);
        },
    ],
],
```

Full config.php after update:

```php
<?php

return [
    'debug' => true,
    'url' => 'http://localhost:5173',

    'arnoson.kirby-vite' => [
        'entry' => 'assets/src/js/app.js',
        'outDir' => 'public/assets',
    ],

    'routes' => [
        [
            'pattern' => 'genre/(:any)',
            'action'  => function ($genre) {
                return page('games')->render(['genre' => $genre]);
            },
        ],
    ],
];
```

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: add search and genre pages"
```

---

### Task 7: Loupe Search Integration

**Files:**
- Create: (none — Loupe is configured via Kirby plugin)
- Modify: `site/config/config.php` (add Loupe config)

Loupe auto-indexes content via Kirby hooks when configured. The plugin docs show minimal config needed.

- [ ] **Step 1: Add Loupe configuration to `site/config/config.php`**

```php
'arnoson.loupe' => [
    'database' => __DIR__ . '/../../sqlite/diario.db',
    'index' => $site->index(),
    'fields' => ['title', 'summary', 'text'],
],
```

Full config.php after update:

```php
<?php

return [
    'debug' => true,
    'url' => 'http://localhost:5173',

    'arnoson.kirby-vite' => [
        'entry' => 'assets/src/js/app.js',
        'outDir' => 'public/assets',
    ],

    'arnoson.loupe' => [
        'database' => __DIR__ . '/../../sqlite/diario.db',
        'fields' => ['title', 'summary', 'text'],
    ],

    'routes' => [
        [
            'pattern' => 'genre/(:any)',
            'action'  => function ($genre) {
                return page('games')->render(['genre' => $genre]);
            },
        ],
    ],
];
```

- [ ] **Step 2: Update search controller to use Loupe**

Modify `site/controllers/search.php`:

```php
<?php

return function ($site) {
    $query = get('q');
    $results = [];

    if ($query && strlen(trim($query)) > 0) {
        // Use Loupe for full-text search
        try {
            $results = loupe($query)->paginate(20);
        } catch (\Exception $e) {
            // Fallback to Kirby built-in search
            $results = $site->search(trim($query), 'title|summary|text');
            $results = $results->paginate(20);
        }
    }

    return [
        'query' => $query,
        'results' => $results ?? [],
        'pagination' => $results->pagination() ?? null,
    ];
};
```

- [ ] **Step 3: Commit**

```bash
git add -A && git commit -m "feat: configure Loupe SQLite search"
```

---

### Task 8: Meta Kit SEO Configuration

**Files:**
- Create: (none — Meta Kit is configured via blueprints)

Meta Kit is configured through Kirby blueprints and config. The plugin adds fields automatically to pages.

- [ ] **Step 1: Add Meta Kit config to `site/config/config.php`**

```php
'tearoom1.meta-kit' => [
    'sitemap' => true,
    'robots' => true,
],
```

Full config.php after final update:

```php
<?php

return [
    'debug' => true,
    'url' => 'http://localhost:5173',

    'arnoson.kirby-vite' => [
        'entry' => 'assets/src/js/app.js',
        'outDir' => 'public/assets',
    ],

    'arnoson.loupe' => [
        'database' => __DIR__ . '/../../sqlite/diario.db',
        'fields' => ['title', 'summary', 'text'],
    ],

    'tearoom1.meta-kit' => [
        'sitemap' => true,
        'robots' => true,
    ],

    'routes' => [
        [
            'pattern' => 'genre/(:any)',
            'action'  => function ($genre) {
                return page('games')->render(['genre' => $genre]);
            },
        ],
    ],
];
```

- [ ] **Step 2: Verify Vite build works**

Run: `bun run build`
Expected: Creates `public/assets/` with compiled CSS + JS manifest.

- [ ] **Step 3: Verify dev server works**

Run: `bun run dev` (in background)
Expected: Vite dev server starts on port 5173.

- [ ] **Step 4: Commit**

```bash
git add -A && git commit -m "feat: configure Meta Kit SEO"
```

---

### Task 9: Content Seed — Sample Game + Post

**Files:**
- Create: `content/games/elden-ring/game.txt`
- Create: `content/games/elden-ring/elden-ring.png.txt`
- Create: `content/games/elden-ring/shadow-of-the-erdtree/post.txt`
- Create: `content/games/elden-ring/shadow-of-the-erdtree/header.png.txt`

- [ ] **Step 1: Create sample game `content/games/elden-ring/game.txt`**

```
Title: Elden Ring
----
Summary: THE NEW FANTASY ACTION RPG. Rise, Tarnished, and be guided by grace to brandish the power of the Elden Ring and become an Elden Lord in the Lands Between.
----
Release date: 2022-02-25
----
Developer: FromSoftware
----
Publisher: Bandai Namco
----
Genres: rpg, mundo-abierto, aventura
----
Featured: true
----
```

- [ ] **Step 2: Create `content/games/elden-ring/elden-ring.png.txt`**

```
Title: Elden Ring Cover
----
Template: cover
----
```

- [ ] **Step 3: Create sample post `content/games/elden-ring/shadow-of-the-erdtree/post.txt`**

```
Title: Shadow of the Erdtree Review
----
Summary: FromSoftware has done it again. Shadow of the Erdtree is not just a downloadable expansion—it's a masterclass in world design.
----
Text: (text: FromSoftware has delivered what many are calling the greatest expansion ever made. Shadow of the Erdtree takes everything that made the base game special and amplifies it to new heights.

The new map is vast, interconnected, and filled with secrets that reward exploration. New weapon types, bosses, and lore additions make this an essential return to the Lands Between.

**Verdict:** A masterpiece.
----
Date: 2026-06-15
----
Author: John Doe
----
```

- [ ] **Step 4: Create `content/games/elden-ring/shadow-of-the-erdtree/header.png.txt`**

```
Title: Shadow of the Erdtree
----
Template: header
----
```

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: add sample game and post content"
```

---

## Self-Review Checklist

- Spec coverage: All spec sections have corresponding tasks — homepage hero (Task 4,5), genre grid (Task 5), game page with posts grid (Task 5), post page (Task 5), search (Task 6,7), genre page (Task 6), SEO (Task 8), navigation (Task 3)
- No placeholders, TBDs, or TODOs
- Type consistency: GamePage model methods match template usage, PostPage model methods match template usage
- File paths are exact and consistent across tasks
