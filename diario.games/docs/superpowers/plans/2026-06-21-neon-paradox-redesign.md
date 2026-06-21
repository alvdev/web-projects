# Neon Paradox Visual Redesign Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the visual identity of diario.games templates and snippets with a hot-pink + electric-purple band-poster / cyberpunk look inspired by the Neon Paradox Dribbble concept, using Anton (display) + Caveat (script) + Barlow Condensed (body) + Atomic Marker (graffiti) typography. No backend logic changes; the data model, Kirby page tree, controllers, and routes stay the same.

**Architecture:** Tokens-first build. We rewrite the Tailwind v4 `@theme` block first, add `@font-face` for the local Atomic Marker font, then ship a set of `@utility` classes (`neon-glow-*`, `marquee`, `glow-pulse`, `drop-cap`, `band-*`, `grain`) that all snippets and templates consume. New reusable snippets (`marquee.php`, `script-accent.php`) are added before any template is touched, so templates can call them. The genre-phrase map is added to the existing `GamePage` model as a `const`; hero accent phrases go into `site/config/config.php` under a `diario` namespace. Each template gets a single, atomic pass.

**Tech Stack:** Kirby CMS 5 (PHP 8.1+), Tailwind CSS v4 (via `@theme` / `@utility`), Vite 6, Bun, no JS framework additions (vanilla CSS keyframes only).

---

## File Structure

### Create
- `public/assets/fonts/atomic-marker.woff2` — self-hosted graffiti font (converted from the Set Sail Studios OTF)
- `public/assets/fonts/atomic-marker.otf` — original file kept as a fallback reference
- `site/snippets/marquee.php` — pure CSS scrolling text strip
- `site/snippets/script-accent.php` — small Caveat script line snippet

### Modify
- `assets/src/css/app.css` — replace `@theme` block, add `@font-face`, add new `@utility` classes
- `site/models/game.php` — add `GENRE_PHRASES` const
- `site/config/config.php` — add `diario.heroAccentPhrases` key
- `site/snippets/header.php` — new look, Google Fonts links, Atomic Marker logo
- `site/snippets/footer.php` — new look
- `site/snippets/hero.php` — new look, hero accent phrase
- `site/snippets/game-card.php` — new look, sharp corners, hot-pink border
- `site/snippets/post-card.php` — new look, unified hot-pink accent
- `site/snippets/genre-nav.php` — new look
- `site/snippets/affiliate-banner.php` — new look (hot-pink dashed border, Caveat "PUBLICIDAD" eyebrow). Read first; if file doesn't exist, this task is a no-op.
- `site/templates/home.php` — new band layout, marquee insertions
- `site/templates/game.php` — new cover, drop cap, info row
- `site/templates/post.php` — new header, drop cap, prose styles
- `site/templates/games.php` — new title, grid treatment
- `site/templates/genre.php` — new title, marquee, empty state
- `site/templates/search.php` — new form, results card, empty state
- `site/templates/default.php` — new 404 look

---

## Task 1: Download and convert Atomic Marker font

**Files:**
- Create: `public/assets/fonts/atomic-marker.otf`
- Create: `public/assets/fonts/atomic-marker.woff2`

- [ ] **Step 1: Download the OTF**

Run:
```bash
mkdir -p public/assets/fonts
curl -L -o public/assets/fonts/atomic-marker.otf \
  "https://font.gooova.com/storage/unpacked_archives/15676/Set%20Sail%20Studios%20-%20Atomic%20Marker%20Extras.otf"
```

Expected: file downloaded, `ls -la public/assets/fonts/atomic-marker.otf` shows > 0 bytes.

- [ ] **Step 2: Verify file integrity**

Run:
```bash
file public/assets/fonts/atomic-marker.otf
```

Expected: output contains "OpenType font" or "OTF". If output says "HTML" or "ASCII text", the URL is gated; stop and fall back to using `Bungee` from Google Fonts (skip the rest of this task and update the `@theme` block in Task 2 to use `"Bungee"` as the graffiti font fallback).

- [ ] **Step 3: Convert OTF to woff2**

Try woff2_compress first (Google's reference tool):
```bash
which woff2_compress || apt list --installed 2>/dev/null | grep woff2
```

If `woff2_compress` is available:
```bash
woff2_compress public/assets/fonts/atomic-marker.otf
```

Otherwise install it (Debian/Ubuntu):
```bash
sudo apt install woff2
woff2_compress public/assets/fonts/atomic-marker.otf
```

If `apt` is unavailable, use the Python fonttools fallback:
```bash
pip install fonttools brotli
python3 -c "from fontTools.ttLib import TTFont; f=TTFont('public/assets/fonts/atomic-marker.otf'); f.flavor='woff2'; f.save('public/assets/fonts/atomic-marker.woff2')"
```

Expected: `public/assets/fonts/atomic-marker.woff2` exists, `file public/assets/fonts/atomic-marker.woff2` reports "WOFF2" or "Web Open Font Format".

- [ ] **Step 4: Verify both files**

Run:
```bash
ls -la public/assets/fonts/
```

Expected: both `.otf` and `.woff2` files present, `.woff2` is roughly 30-50% the size of `.otf`.

- [ ] **Step 5: Commit**

```bash
git add public/assets/fonts/atomic-marker.otf public/assets/fonts/atomic-marker.woff2
git commit -m "feat(fonts): add Atomic Marker graffiti font (otf + woff2)"
```

---

## Task 2: Rewrite Tailwind v4 @theme block with new color tokens and font families

**Files:**
- Modify: `assets/src/css/app.css`

- [ ] **Step 1: Replace the file content**

Write the following to `assets/src/css/app.css`:

```css
@import "tailwindcss";
@import url('https://fonts.googleapis.com/css2?family=Anton&family=Caveat:wght@500;700&family=Barlow+Condensed:wght@400;600;800&display=swap');

@font-face {
  font-family: "Atomic Marker";
  src: url('/assets/fonts/atomic-marker.woff2') format('woff2'),
       url('/assets/fonts/atomic-marker.otf') format('opentype');
  font-display: swap;
  font-weight: 400;
  font-style: normal;
}

@utility debug-screens {
  &::before {
    @apply fixed bottom-0 left-0 z-2147483647 rounded-tr-sm bg-yellow-400 px-[0.5em] py-[0.3333333em] text-xs leading-none font-bold text-black;

    @variant sm {
      content: "screen: sm";
    }

    @variant md {
      content: "screen: md";
    }

    @variant lg {
      content: "screen: lg";
    }

    @variant xl {
      content: "screen: xl";
    }
  }
}

@theme {
  --color-bg: #0a0a12;
  --color-surface: #1a1a2e;
  --color-surface-alt: #2a2a4e;
  --color-pink: #ff2bd6;
  --color-purple: #6b1aff;
  --color-yellow: #f0ff00;
  --color-cyan: #00f5ff;
  --color-green: #39ff14;
  --color-text: #f5f5f5;
  --color-muted: #8888a8;
  --color-border: #3a3a5e;
  --color-dark: var(--color-bg);
  --color-neon-cyan: var(--color-cyan);
  --color-neon-magenta: var(--color-pink);
  --color-neon-green: var(--color-green);

  --font-display: "Anton", "Impact", sans-serif;
  --font-script: "Caveat", cursive;
  --font-body: "Barlow Condensed", sans-serif;
  --font-graffiti: "Atomic Marker", "Bungee", cursive;
}

@layer base {
  body {
    @apply bg-bg text-text min-h-screen;
    font-family: var(--font-body);
  }
}

@utility neon-glow-pink {
  text-shadow: 0 0 8px var(--color-pink), 0 0 24px rgba(255, 43, 214, 0.6);
}

@utility neon-glow-yellow {
  text-shadow: 0 0 8px var(--color-yellow), 0 0 24px rgba(240, 255, 0, 0.6);
}

@utility neon-glow-cyan {
  text-shadow: 0 0 6px var(--color-cyan), 0 0 18px rgba(0, 245, 255, 0.5);
}

@utility grain {
  position: relative;
  &::before {
    content: "";
    position: fixed;
    inset: 0;
    pointer-events: none;
    z-index: 1;
    opacity: 0.04;
    background-image:
      radial-gradient(rgba(255, 255, 255, 0.5) 1px, transparent 1px),
      radial-gradient(rgba(255, 255, 255, 0.3) 1px, transparent 1px);
    background-size: 4px 4px, 7px 7px;
    background-position: 0 0, 2px 2px;
  }
}

@utility marquee {
  overflow: hidden;
  position: relative;
  width: 100%;
}

@utility marquee-track {
  display: flex;
  width: max-content;
  animation: marquee-scroll 18s linear infinite;
}

@keyframes marquee-scroll {
  from { transform: translateX(0); }
  to { transform: translateX(-50%); }
}

@utility glow-pulse {
  animation: neon-pulse 2.4s ease-in-out infinite;
}

@keyframes neon-pulse {
  0%, 100% { text-shadow: 0 0 6px currentColor, 0 0 18px currentColor; }
  50% { text-shadow: 0 0 14px currentColor, 0 0 32px currentColor; }
}

@utility drop-cap {
  &::first-letter {
    font-family: var(--font-graffiti);
    color: var(--color-pink);
    font-size: 4em;
    line-height: 0.85;
    float: left;
    margin-right: 0.15em;
    margin-top: 0.05em;
    text-shadow: 0 0 12px rgba(255, 43, 214, 0.6);
  }
}

@utility band-purple {
  background-color: var(--color-purple);
  color: var(--color-bg);
  padding-top: 3rem;
  padding-bottom: 3rem;
}

@utility band-pink {
  background-color: var(--color-pink);
  color: var(--color-bg);
  padding-top: 3rem;
  padding-bottom: 3rem;
}

@media (prefers-reduced-motion: reduce) {
  @utility marquee-track {
    animation: none;
  }
  @utility glow-pulse {
    animation: none;
  }
  * {
    transition: none !important;
  }
}
```

- [ ] **Step 2: Verify the build compiles**

Run:
```bash
bun run build
```

Expected: build succeeds, output written to `public/assets/`. No errors about `@utility`, `@font-face`, or `@theme`. If you see errors about `@layer base` not being valid, remove that block (the body styling is harmless to omit — the `font-body` class on the body will still work; the `@layer base` block is just for when the body has no other class).

- [ ] **Step 3: Verify the @font-face resolves**

Run:
```bash
bun run dev &
sleep 4
curl -s -o /dev/null -w "%{http_code}" http://localhost:5173/assets/fonts/atomic-marker.woff2
```

Expected: `200` (the Vite dev server serves static assets from `public/`). If `404`, the Vite config might not serve `public/assets/fonts/` — fall back to placing the font under `assets/src/fonts/` and importing via Vite (out of scope for this task; document the issue and skip woff2 for now).

Stop the dev server: `kill %1` (or `pkill -f vite`).

- [ ] **Step 4: Commit**

```bash
git add assets/src/css/app.css
git commit -m "feat(css): rewrite theme tokens for Neon Paradox look (pink/purple/yellow, Anton/Caveat/Barlow/Atomic Marker)"
```

---

## Task 3: Add GENRE_PHRASES constant to GamePage model and hero accent phrases to config

**Files:**
- Modify: `site/models/game.php`
- Modify: `site/config/config.php`

- [ ] **Step 1: Add GENRE_PHRASES to GamePage model**

Edit `site/models/game.php`. Replace the entire class with:

```php
<?php

class GamePage extends \Kirby\Cms\Page
{
    public const GENRE_PHRASES = [
        'Acción' => 'dispara primero',
        'Aventura' => 'explora lo oculto',
        'RPG' => 'sumérgete',
        'Shooter' => 'aprieta el gatillo',
        'Estrategia' => 'piensa, luego vence',
        'Simulación' => 'vive otras vidas',
        'Deportes y Carreras' => 'a toda velocidad',
        'Terror' => 'no mires atrás',
        'Puzzle' => 'piezas en su sitio',
        'Supervivencia' => 'aguanta la noche',
        'Mundo abierto' => 'cabalga libre',
        'Multijugador' => 'juntos o nada',
    ];

    public function genres(): string
    {
        return $this->content()->get('genres')->value() ?? '';
    }

    public function genreList(): array
    {
        return array_map('trim', explode(',', $this->genres()));
    }

    public function tags(): string
    {
        return $this->content()->get('tags')->value() ?? '';
    }

    public function tagList(): array
    {
        return array_map('trim', explode(',', $this->tags()));
    }

    public function posts(): \Kirby\Cms\Pages
    {
        return $this->children()->sortBy('date', 'desc');
    }

    public function cover(): ?\Kirby\Cms\File
    {
        return $this->files()->template('cover')->first();
    }

    public function hero(): ?\Kirby\Cms\File
    {
        return $this->files()->template('hero')->first();
    }

    public function screenshots(): array
    {
        $raw = $this->content()->get('Screenshots')->value() ?? '';
        if (empty(trim($raw))) return [];
        return array_map(function ($id) {
            $id = trim($id);
            return [
                'thumb' => 'https://images.igdb.com/igdb/image/upload/t_screenshot_med/' . $id . '.jpg',
                'full'  => 'https://images.igdb.com/igdb/image/upload/t_screenshot_huge/' . $id . '.jpg',
            ];
        }, explode(',', $raw));
    }

    public function releaseDate(): string
    {
        return $this->content()->get('ReleaseDate')->value() ?? '';
    }

    public function releaseYear(): string
    {
        $date = $this->releaseDate();
        if (preg_match('/^\d{4}/', $date, $m)) {
            return $m[0];
        }
        return $date;
    }
}
```

- [ ] **Step 2: Add heroAccentPhrases to config**

Edit `site/config/config.php`. Inside the returned array (after the `'tearoom1.meta-kit'` block, before `'routes'`), add:

```php
    'diario' => [
        'heroAccentPhrases' => [
            'llega ahora',
            'revelado',
            'rumoreado',
            'ya disponible',
            'a la vista',
            'en camino',
        ],
    ],
```

- [ ] **Step 3: Verify the PHP parses**

Run:
```bash
php -l site/models/game.php
php -l site/config/config.php
```

Expected: both report "No syntax errors detected".

- [ ] **Step 4: Commit**

```bash
git add site/models/game.php site/config/config.php
git commit -m "feat(data): add GENRE_PHRASES const and hero accent phrases config"
```

---

## Task 4: Create marquee.php snippet

**Files:**
- Create: `site/snippets/marquee.php`

- [ ] **Step 1: Write the snippet**

Write the following to `site/snippets/marquee.php`:

```php
<?php
$phrase = $phrase ?? 'ÚLTIMOS POSTS';
$color = $color ?? 'pink';
$bg = $bg ?? 'black';
$speed = $speed ?? 'medium';

$colorVar = match ($color) {
    'yellow' => 'var(--color-yellow)',
    'purple' => 'var(--color-purple)',
    default  => 'var(--color-pink)',
};

$bgClass = match ($bg) {
    'pink'   => 'band-pink',
    'purple' => 'band-purple',
    'transparent' => '',
    default  => 'bg-bg',
};

$dur = match ($speed) {
    'slow'   => '30s',
    'fast'   => '10s',
    default  => '18s',
};

$separator = '★';
$display = "{$phrase} {$separator} {$phrase} {$separator} {$phrase} {$separator} {$phrase} {$separator} {$phrase} {$separator}";
$display2 = $display;

$style = "color: {$colorVar}; text-shadow: 0 0 8px {$colorVar}, 0 0 24px {$colorVar}; --marquee-duration: {$dur};";
?>
<div class="marquee <?= $bgClass ?>" style="<?= $bg === 'transparent' ? 'background:transparent;' : '' ?>">
  <div class="marquee-track" style="<?= $style ?>">
    <span class="font-graffiti uppercase tracking-widest text-3xl md:text-5xl whitespace-nowrap px-4"><?= $display ?></span>
    <span class="font-graffiti uppercase tracking-widest text-3xl md:text-5xl whitespace-nowrap px-4" aria-hidden="true"><?= $display2 ?></span>
  </div>
</div>
```

- [ ] **Step 2: Verify the snippet parses**

Run:
```bash
php -l site/snippets/marquee.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add site/snippets/marquee.php
git commit -m "feat(snippet): add marquee scrolling text strip"
```

---

## Task 5: Create script-accent.php snippet

**Files:**
- Create: `site/snippets/script-accent.php`

- [ ] **Step 1: Write the snippet**

Write the following to `site/snippets/script-accent.php`:

```php
<?php
$text = $text ?? '';
$color = $color ?? 'pink';
$size = $size ?? 'md';

if ($text === '') return;

$colorVar = match ($color) {
    'yellow' => 'var(--color-yellow)',
    default  => 'var(--color-pink)',
};

$sizeClass = match ($size) {
    'sm' => 'text-lg md:text-2xl',
    'lg' => 'text-3xl md:text-5xl',
    default => 'text-2xl md:text-3xl',
};

$style = "color: {$colorVar}; text-shadow: 0 0 6px {$colorVar};";
?>
<span class="font-script <?= $sizeClass ?>" style="<?= $style ?>"><?= htmlspecialchars($text) ?></span>
```

- [ ] **Step 2: Verify the snippet parses**

Run:
```bash
php -l site/snippets/script-accent.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add site/snippets/script-accent.php
git commit -m "feat(snippet): add script-accent Caveat line snippet"
```

---

## Task 6: Redesign header.php

**Files:**
- Modify: `site/snippets/header.php`

- [ ] **Step 1: Replace the file content**

Write the following to `site/snippets/header.php`:

```php
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <?= vite()->js('assets/src/js/app.js') ?>
    <?= vite()->css('assets/src/js/app.js') ?>
    <?php snippet('meta-kit/seo') ?>
</head>
<body class="debug-screens grain bg-bg text-text min-h-screen">

<header class="border-b-2 border-pink bg-bg sticky top-0 z-50" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
    <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between gap-4">
        <a href="/" class="flex items-center gap-1 shrink-0 neon-glow-pink">
            <span class="font-graffiti text-2xl md:text-3xl tracking-widest text-pink uppercase">Diario</span><span class="font-graffiti text-2xl md:text-3xl tracking-widest text-yellow uppercase">.Games</span>
        </a>

        <div class="hidden md:block flex-1 min-w-0">
            <?php snippet('genre-nav') ?>
        </div>

        <a href="<?= url('search') ?>" class="text-pink hover:text-yellow transition shrink-0">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </a>

        <button type="button" class="md:hidden text-pink" aria-label="Abrir menú" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
    </div>
    <div id="mobile-menu" class="hidden md:hidden border-t border-pink/40 bg-bg">
        <?php snippet('genre-nav') ?>
    </div>
</header>

<main class="max-w-7xl mx-auto px-4 py-6 relative" style="z-index: 2;">
```

- [ ] **Step 2: Verify the PHP parses**

Run:
```bash
php -l site/snippets/header.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Boot the dev server and visually confirm**

Run:
```bash
bun run dev &
sleep 4
```

Open `http://localhost:8888` in a browser. Confirm:
- Header is solid black with a hot-pink glowing bottom border
- Logo renders in graffiti font (Atomic Marker if loaded, otherwise Bungee fallback) with "Diario" in pink and ".Games" in yellow
- Genre nav is visible on desktop, hidden on mobile
- Search icon is hot-pink
- Hamburger appears on mobile (resize browser to < 768px)

Stop the dev server: `pkill -f vite` or `pkill -f "php -S"`.

- [ ] **Step 4: Commit**

```bash
git add site/snippets/header.php
git commit -m "feat(snippet): redesign header with graffiti logo and pink glow border"
```

---

## Task 7: Redesign genre-nav.php

**Files:**
- Modify: `site/snippets/genre-nav.php`

- [ ] **Step 1: Replace the file content**

Write the following to `site/snippets/genre-nav.php`:

```php
<?php

$games = $site->find('games')->children();
$allGenres = [];
foreach ($games as $game) {
    foreach ($game->genreList() as $genre) {
        $genre = trim($genre);
        if ($genre) $allGenres[$genre] = true;
    }
}
ksort($allGenres);
$currentGenre = urldecode(param('genre') ?? '');
?>
<nav class="flex gap-4 overflow-x-auto font-body font-semibold uppercase tracking-widest text-sm scrollbar-hide">
    <a href="<?= url('games') ?>" class="text-muted hover:text-pink transition whitespace-nowrap">All</a>
    <?php foreach (array_keys($allGenres) as $genre): ?>
    <a href="<?= url('genre/' . urlencode($genre)) ?>" class="whitespace-nowrap transition pb-1 border-b-2 <?= $currentGenre === $genre ? 'text-pink border-pink' : 'text-muted border-transparent hover:text-pink' ?>">
        <?= htmlspecialchars($genre) ?>
    </a>
    <?php endforeach ?>
</nav>
<style>.scrollbar-hide::-webkit-scrollbar{display:none}.scrollbar-hide{-ms-overflow-style:none;scrollbar-width:none}</style>
```

- [ ] **Step 2: Verify the PHP parses**

Run:
```bash
php -l site/snippets/genre-nav.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add site/snippets/genre-nav.php
git commit -m "feat(snippet): redesign genre-nav with active state and scrollbar hide"
```

---

## Task 8: Redesign footer.php

**Files:**
- Modify: `site/snippets/footer.php`

- [ ] **Step 1: Replace the file content**

Write the following to `site/snippets/footer.php`:

```php
</main>

<footer class="border-t-2 border-pink mt-12 py-8 bg-bg relative" style="z-index: 2; box-shadow: 0 0 16px rgba(255,43,214,0.4);">
    <div class="max-w-7xl mx-auto px-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center">
            <div>
                <span class="font-graffiti text-2xl tracking-widest text-pink uppercase neon-glow-pink">Diario</span><span class="font-graffiti text-2xl tracking-widest text-yellow uppercase">.Games</span>
            </div>
            <nav class="flex flex-wrap justify-center gap-4 font-body uppercase tracking-widest text-sm text-muted">
                <a href="/" class="hover:text-pink transition">Home</a>
                <a href="<?= url('games') ?>" class="hover:text-pink transition">Juegos</a>
                <a href="<?= url('search') ?>" class="hover:text-pink transition">Buscar</a>
                <a href="#" class="hover:text-pink transition">About</a>
                <a href="#" class="hover:text-pink transition">Contacto</a>
            </nav>
            <div class="flex justify-end gap-3 text-pink text-xl">
                <a href="#" aria-label="Twitter" class="hover:text-yellow transition"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53A4.48 4.48 0 0012 7.5v1A10.66 10.66 0 013 4.5s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg></a>
                <a href="#" aria-label="Instagram" class="hover:text-yellow transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></a>
                <a href="#" aria-label="YouTube" class="hover:text-yellow transition"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23 7s-.2-1.5-.9-2.2c-.8-.9-1.7-.9-2.1-1C16.7 3.5 12 3.5 12 3.5s-4.7 0-8 .3c-.4 0-1.3 0-2.1 1C1.2 5.5 1 7 1 7S.8 8.7.8 10.5v1.9c0 1.8.2 3.5.2 3.5s.2 1.5.9 2.2c.8.9 1.9.9 2.4 1 1.7.2 7.7.3 7.7.3s4.7 0 8-.3c.4 0 1.3-.1 2.1-1 .7-.7.9-2.2.9-2.2s.2-1.7.2-3.5v-1.9c0-1.8-.2-3.5-.2-3.5zM9.5 14V8l6 3-6 3z"/></svg></a>
            </div>
        </div>
        <div class="mt-6 pt-4 border-t border-border flex flex-wrap justify-between items-center gap-2 text-xs text-muted font-body">
            <div>&copy; <?= date('Y') ?> Diario.Games. Todos los derechos reservados.</div>
            <div class="flex gap-4">
                <a href="#" class="hover:text-pink transition">Términos</a>
                <a href="#" class="hover:text-pink transition">Privacidad</a>
            </div>
        </div>
    </div>
</footer>

</body>
</html>
```

- [ ] **Step 2: Verify the PHP parses**

Run:
```bash
php -l site/snippets/footer.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add site/snippets/footer.php
git commit -m "feat(snippet): redesign footer with 3-col grid and pink accent"
```

---

## Task 9: Redesign game-card.php

**Files:**
- Modify: `site/snippets/game-card.php`

- [ ] **Step 1: Replace the file content**

Write the following to `site/snippets/game-card.php`:

```php
<?php $game = $game ?? $page; ?>
<a href="<?= $game->url() ?>" class="block bg-bg border-2 border-pink overflow-hidden hover:border-yellow transition group" style="box-shadow: 0 0 0 0 rgba(255,43,214,0); transition: box-shadow 200ms ease;" onmouseover="this.style.boxShadow='0 0 16px rgba(255,43,214,0.5)'" onmouseout="this.style.boxShadow='0 0 0 0 rgba(255,43,214,0)'">
    <div class="aspect-[16/9] bg-surface-alt overflow-hidden">
        <?php if ($hero = $game->hero()): ?>
        <img src="<?= $hero->url() ?>" alt="<?= $game->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php elseif ($cover = $game->cover()): ?>
        <img src="<?= $cover->url() ?>" alt="<?= $game->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center font-graffiti text-pink text-xs uppercase tracking-widest">No cover</div>
        <?php endif ?>
    </div>
    <div class="p-3 bg-surface">
        <h3 class="font-display text-sm md:text-base uppercase text-text leading-tight line-clamp-2"><?= $game->title() ?></h3>
        <div class="flex items-center gap-2 mt-1 font-body text-xs text-muted">
            <span class="text-pink uppercase tracking-wider"><?= htmlspecialchars(implode(', ', array_slice($game->genreList(), 0, 2))) ?></span>
            <?php if ($game->releaseYear()): ?>
            <span class="text-cyan">• <?= $game->releaseYear() ?></span>
            <?php endif ?>
        </div>
    </div>
</a>
```

- [ ] **Step 2: Verify the PHP parses**

Run:
```bash
php -l site/snippets/game-card.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add site/snippets/game-card.php
git commit -m "feat(snippet): redesign game-card with sharp corners and pink border"
```

---

## Task 10: Redesign post-card.php

**Files:**
- Modify: `site/snippets/post-card.php`

- [ ] **Step 1: Replace the file content**

Write the following to `site/snippets/post-card.php`:

```php
<?php $post = $post ?? $page; ?>
<a href="<?= $post->url() ?>" class="block bg-bg border-2 border-pink overflow-hidden hover:border-yellow transition group" style="box-shadow: 0 0 0 0 rgba(255,43,214,0); transition: box-shadow 200ms ease;" onmouseover="this.style.boxShadow='0 0 16px rgba(255,43,214,0.5)'" onmouseout="this.style.boxShadow='0 0 0 0 rgba(255,43,214,0)'">
    <div class="aspect-[16/9] bg-surface-alt overflow-hidden">
        <?php if ($image = $post->headerImage()): ?>
        <img src="<?= $image->url() ?>" alt="<?= $post->title() ?>" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
        <?php else: ?>
        <div class="w-full h-full flex items-center justify-center font-graffiti text-pink text-xs uppercase tracking-widest">No image</div>
        <?php endif ?>
    </div>
    <div class="p-3 bg-surface">
        <h3 class="font-display text-sm md:text-base uppercase text-text leading-tight line-clamp-2"><?= $post->title() ?></h3>
        <div class="font-body text-xs text-muted mt-1">
            <?php if ($game = $post->parentGame()): ?>
            <span class="block font-script text-base md:text-lg text-yellow leading-none">sobre <?= $game->title() ?></span>
            <?php endif ?>
            <?php if ($post->postDate()): ?>
            <span class="block mt-1"><?= $post->postDate() ?><?php if (preg_match('/^(\d{4})/', (string)$post->postDate(), $m)): ?> <span class="text-cyan"><?= $m[1] ?></span><?php endif ?></span>
            <?php endif ?>
        </div>
    </div>
</a>
```

Note: the year is extracted from `postDate()` only when the string starts with a 4-digit year. The `m[1]` capture group is the year without the rest of the date — if the date format is `2024-03-15`, only `2024` is shown in cyan. If the date is `15 March 2024`, no cyan year appears (which is acceptable).

- [ ] **Step 2: Verify the PHP parses**

Run:
```bash
php -l site/snippets/post-card.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add site/snippets/post-card.php
git commit -m "feat(snippet): redesign post-card with unified pink accent and yellow script"
```

---

## Task 11: Redesign hero.php

**Files:**
- Modify: `site/snippets/hero.php`

- [ ] **Step 1: Replace the file content**

Write the following to `site/snippets/hero.php`:

```php
<?php
$allGames = $site->find('games')->children()->sortBy('modified', 'desc');
$allPosts = $allGames->children()->filterBy('template', 'post')->sortBy('date', 'desc');
$latestPost = $allPosts->first();
if ($latestPost):
    $featured = $latestPost;
    $isPost = true;
else:
    $featured = $allGames->filterBy('featured', 'true')->first() ?? $allGames->first();
    $isPost = false;
endif;

$accentPhrases = kirby()->option('diario.heroAccentPhrases', ['llega ahora', 'revelado', 'ya disponible']);
$accent = $accentPhrases[array_rand($accentPhrases)];

$eyebrowBg = $isPost ? 'bg-pink' : 'bg-yellow';
$eyebrowColor = $isPost ? 'text-bg' : 'text-bg';
?>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-8">
    <?php if ($featured): ?>
    <a href="<?= $featured->url() ?>" class="lg:col-span-2 relative bg-bg border-2 border-pink overflow-hidden group" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
        <div class="absolute top-3 right-3 z-10 -rotate-2 <?= $eyebrowBg ?> <?= $eyebrowColor ?> font-display text-xs uppercase tracking-widest px-2 py-1">
            <?= $isPost ? 'Último post' : 'Featured' ?>
        </div>
        <?php $heroImg = $isPost ? ($featured->parent()->cover() ?? $featured->parent()->hero()) : ($featured->cover() ?? $featured->hero()) ?>
        <?php if ($heroImg): ?>
        <img src="<?= $heroImg->url() ?>" alt="<?= $featured->title() ?>" class="w-full aspect-[21/9] object-cover">
        <div class="absolute inset-0 bg-gradient-to-t from-bg via-bg/70 to-transparent"></div>
        <?php else: ?>
        <div class="w-full aspect-[21/9]" style="background: linear-gradient(135deg, var(--color-pink), var(--color-purple));"></div>
        <?php endif ?>
        <div class="relative aspect-[21/9] flex items-end p-6 -mt-[var(--aspect-h,80%)]">
            <div class="w-full">
                <h2 class="font-display text-3xl md:text-5xl lg:text-6xl uppercase text-text leading-none"><?= $featured->title() ?></h2>
                <p class="font-script text-2xl md:text-3xl text-pink mt-1 neon-glow-pink"><?= $accent ?></p>
                <p class="font-body text-sm md:text-base text-muted mt-3 max-w-xl line-clamp-2"><?= $isPost ? $featured->text()->kti() : $featured->summary()->kti() ?></p>
                <div class="mt-4 inline-block border-2 border-pink text-pink font-display text-sm uppercase tracking-widest px-5 py-2 neon-glow-pink">► Leer</div>
            </div>
        </div>
    </a>
    <?php endif ?>

    <div class="bg-bg border-2 border-pink p-4" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
        <h3 class="font-display text-xs uppercase tracking-widest text-yellow glow-pulse mb-3">⚡ Trending</h3>
        <div class="space-y-3">
            <?php $trending = $allGames->limit(4); ?>
            <?php foreach ($trending as $game): ?>
            <a href="<?= $game->url() ?>" class="block font-body font-semibold text-sm text-muted hover:text-pink transition pb-2 border-b border-pink/20 last:border-0">
                <?= $game->title() ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
</div>
```

- [ ] **Step 2: Verify the PHP parses**

Run:
```bash
php -l site/snippets/hero.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add site/snippets/hero.php
git commit -m "feat(snippet): redesign hero with Caveat accent and pink border"
```

---

## Task 12: Redesign affiliate-banner.php (if file exists)

**Files:**
- Modify: `site/snippets/affiliate-banner.php` (read first; skip if absent)

- [ ] **Step 1: Check if the file exists**

Run:
```bash
ls site/snippets/affiliate-banner.php
```

If the file does not exist, skip the rest of this task and commit a no-op:

```bash
git commit --allow-empty -m "chore(snippet): affiliate-banner not present, skipping redesign"
```

- [ ] **Step 2: If it exists, read the current content**

Run:
```bash
cat site/snippets/affiliate-banner.php
```

Then apply the following restyle rules to whatever HTML structure the file uses:

- Wrap the existing banner content in `<div class="border-2 border-dashed border-pink p-4 bg-bg/50">`
- Above the existing content, add: `<div class="font-script text-base text-yellow neon-glow-yellow mb-2">publicidad</div>`
- Replace any `rounded-*` classes with no equivalent (sharp corners)
- Replace any `border-border` or default border color with `border-pink`

If the file uses Kirby's panel field structure (e.g. `<?php foreach ($programs as $p): ?>`), keep that logic intact and only restyle the wrapping `<div>` and any inner element classes.

- [ ] **Step 3: Verify PHP parses**

Run:
```bash
php -l site/snippets/affiliate-banner.php
```

Expected: "No syntax errors detected".

- [ ] **Step 4: Commit**

```bash
git add site/snippets/affiliate-banner.php
git commit -m "feat(snippet): redesign affiliate-banner with pink dashed border and Caveat label"
```

---

## Task 13: Redesign home.php

**Files:**
- Modify: `site/templates/home.php`

- [ ] **Step 1: Read the current home.php to confirm the controller sets `$genreGames`**

Run:
```bash
ls site/controllers/
cat site/controllers/home.php 2>/dev/null || echo "no controller"
```

The current `site/controllers/home.php` limits each genre to 2 games (`->limit(2)`). The new layout uses 3 cards per genre, so update the controller:

Replace `)->limit(2);` with `)->limit(3);` in `site/controllers/home.php` (line 21).

Verify:
```bash
php -l site/controllers/home.php
```

Expected: "No syntax errors detected".

- [ ] **Step 2: Replace home.php with the new band layout**

Write the following to `site/templates/home.php`:

```php
<?php snippet('header') ?>

<?php snippet('hero') ?>

<?php snippet('marquee', ['phrase' => 'Últimos posts', 'color' => 'pink', 'bg' => 'black']) ?>

<?php
$bannerConfig = site()->alvAffBanners();
$hasEnabledPrograms = $bannerConfig['enabled'] && !empty(array_filter($bannerConfig['programs'], fn($p) => $p['enabled']));
$bandColors = ['purple', 'pink'];
?>

<?php
$genreChunks = array_chunk(array_keys($genreGames), 2);
$bandIdx = 0;
?>
<?php foreach ($genreChunks as $chunk): ?>
    <?php $bandColor = $bandColors[$bandIdx % 2]; $bandIdx++; ?>
    <section class="band-<?= $bandColor ?>">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <?php foreach ($chunk as $genre):
                    $games = $genreGames[$genre];
                    $phrase = \GamePage::GENRE_PHRASES[$genre] ?? 'descubre';
                ?>
                    <div>
                        <div class="flex items-end justify-between mb-4">
                            <h2 class="font-display text-4xl md:text-6xl uppercase text-bg leading-none"><?= htmlspecialchars($genre) ?></h2>
                            <?php snippet('script-accent', ['text' => $phrase, 'color' => 'yellow', 'size' => 'md']) ?>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <?php foreach (array_slice($games, 0, 3) as $game): ?>
                                <?php snippet('game-card', ['game' => $game]) ?>
                            <?php endforeach ?>
                        </div>
                        <div class="mt-4">
                            <a href="<?= url('genre/' . urlencode($genre)) ?>" class="inline-block border-2 border-bg text-bg font-display text-sm uppercase tracking-widest px-5 py-2 hover:bg-bg hover:text-pink transition">► Ver <?= htmlspecialchars($genre) ?></a>
                        </div>
                    </div>
                <?php endforeach ?>
            </div>
        </div>
    </section>
    <?php if ($hasEnabledPrograms && $bandIdx % 2 === 0): ?>
        <div class="max-w-7xl mx-auto px-4 py-6">
            <?php snippet('affiliate-banner', ['grid' => true, 'itemCount' => $bandIdx]) ?>
        </div>
    <?php endif ?>
<?php endforeach ?>

<?php snippet('marquee', ['phrase' => 'Explora el catálogo', 'color' => 'yellow', 'bg' => 'pink', 'speed' => 'slow']) ?>

<?php snippet('footer') ?>
```

- [ ] **Step 3: Verify the PHP parses**

Run:
```bash
php -l site/templates/home.php
```

Expected: "No syntax errors detected".

- [ ] **Step 4: Boot dev server and visually confirm**

Run:
```bash
bun run dev &
sleep 4
```

Open `http://localhost:8888`. Confirm:
- Hero renders with the featured game and trending sidebar
- First marquee strip shows "Últimos posts" pink-on-black
- Genre bands alternate purple / pink full-bleed backgrounds
- Each band shows 2 genres side by side on desktop, stacked on mobile
- Each genre section: Anton uppercase title + Caveat yellow accent + 3 game cards + "► Ver [GENRE]" button
- Second marquee at the bottom shows "Explora el catálogo" yellow-on-pink
- Footer renders below

If a genre has fewer than 3 games, fewer cards render (no error). If a genre has zero games, the section still renders with an empty cards grid (acceptable for v1; v2 can hide the section).

Stop the dev server: `pkill -f vite ; pkill -f "php -S"`.

- [ ] **Step 5: Commit**

```bash
git add site/templates/home.php site/controllers/home.php
git commit -m "feat(template): redesign home with alternating genre bands and marquee"
```

---

## Task 14: Redesign game.php

**Files:**
- Modify: `site/templates/game.php`

- [ ] **Step 1: Replace the file content**

Write the following to `site/templates/game.php`:

```php
<?php snippet('header') ?>

<div class="relative bg-bg border-2 border-pink mb-8 overflow-hidden" style="box-shadow: 0 0 20px rgba(255,43,214,0.4);">
<?php $heroImg = $page->cover() ?? $page->hero() ?>
    <div class="aspect-[21/9] bg-cover bg-center flex items-end p-6 md:p-10 relative" style="<?= $heroImg ? 'background-image: url(' . $heroImg->url() . ')' : 'background: linear-gradient(135deg, var(--color-pink), var(--color-purple))' ?>">
        <div class="absolute inset-0 bg-gradient-to-t from-bg via-bg/60 to-transparent"></div>
        <div class="relative z-10 w-full">
            <h1 class="font-display text-4xl md:text-6xl lg:text-7xl uppercase text-text leading-none neon-glow-pink"><?= $page->title() ?></h1>
            <div class="flex flex-wrap gap-2 mt-4 font-body text-sm">
                <?php foreach ($page->genreList() as $genre): ?>
                <span class="px-3 py-1 border border-pink text-pink uppercase tracking-wider"><?= htmlspecialchars($genre) ?></span>
                <?php endforeach ?>
                <?php foreach ($page->tagList() as $tag): ?>
                <span class="px-3 py-1 border border-yellow text-yellow uppercase tracking-wider"><?= htmlspecialchars($tag) ?></span>
                <?php endforeach ?>
                <?php if ($page->releaseDate()): ?>
                <span class="px-3 py-1 text-muted uppercase tracking-wider">Release: <span class="text-cyan"><?= $page->releaseDate() ?></span></span>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <div class="lg:col-span-2">
        <?php if ($page->summary()->isNotEmpty()): ?>
        <div class="font-body text-lg text-text leading-relaxed drop-cap">
            <?= $page->summary()->kt() ?>
        </div>
        <?php endif ?>
    </div>
    <aside class="space-y-4">
        <div class="bg-bg border-2 border-pink p-4" style="box-shadow: 0 0 12px rgba(255,43,214,0.3);">
            <h3 class="font-display text-xs uppercase tracking-widest text-yellow mb-2">Ficha</h3>
            <dl class="font-body text-sm space-y-1">
                <?php if ($page->developer()->isNotEmpty()): ?>
                <div><dt class="text-pink uppercase text-xs">Developer</dt><dd class="text-text"><?= $page->developer() ?></dd></div>
                <?php endif ?>
                <?php if ($page->publisher()->isNotEmpty()): ?>
                <div><dt class="text-pink uppercase text-xs">Publisher</dt><dd class="text-text"><?= $page->publisher() ?></dd></div>
                <?php endif ?>
                <?php if ($page->releaseDate()): ?>
                <div><dt class="text-pink uppercase text-xs">Release</dt><dd class="text-cyan"><?= $page->releaseDate() ?></dd></div>
                <?php endif ?>
            </dl>
        </div>
        <?php if (!empty($page->genreList()) || !empty($page->tagList())): ?>
        <div class="bg-bg border-2 border-pink p-4">
            <h3 class="font-display text-xs uppercase tracking-widest text-yellow mb-2">Tags</h3>
            <div class="flex flex-wrap gap-2 font-body text-xs">
                <?php foreach ($page->genreList() as $g): ?>
                <span class="text-pink uppercase">#<?= htmlspecialchars($g) ?></span>
                <?php endforeach ?>
                <?php foreach ($page->tagList() as $t): ?>
                <span class="text-yellow uppercase">#<?= htmlspecialchars($t) ?></span>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>
    </aside>
</div>

<?php $shots = $page->screenshots() ?>
<?php if (!empty($shots)): ?>
<div class="mt-8 pt-8 border-t border-border">
    <h2 class="font-display text-2xl md:text-3xl uppercase text-pink mb-2">Capturas</h2>
    <?php snippet('script-accent', ['text' => 'mira antes de jugar', 'color' => 'yellow', 'size' => 'md']) ?>
    <ul class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 mt-4">
        <?php foreach ($shots as $shot): ?>
        <li>
            <a href="<?= $shot['full'] ?>" target="_blank" rel="noopener" class="block aspect-video bg-surface-alt border-2 border-transparent hover:border-pink transition overflow-hidden">
                <img src="<?= $shot['thumb'] ?>" alt="" loading="lazy" class="w-full h-full object-cover">
            </a>
        </li>
        <?php endforeach ?>
    </ul>
</div>
<?php endif ?>

<?php $posts = $page->posts() ?>
<?php if ($posts->count() > 0): ?>
<div class="mt-8 pt-8 border-t border-border">
    <div class="flex items-end justify-between mb-6">
        <h2 class="font-display text-2xl md:text-3xl uppercase text-pink">Posts sobre <?= $page->title() ?></h2>
        <?php snippet('script-accent', ['text' => 'léelos todos', 'color' => 'yellow', 'size' => 'md']) ?>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($posts as $post): ?>
            <?php snippet('post-card', ['post' => $post]) ?>
        <?php endforeach ?>
    </div>
</div>
<?php endif ?>

<?php snippet('footer') ?>
```

- [ ] **Step 2: Verify the PHP parses**

Run:
```bash
php -l site/templates/game.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add site/templates/game.php
git commit -m "feat(template): redesign game page with drop cap, info sidebar, pink border"
```

---

## Task 15: Redesign post.php

**Files:**
- Modify: `site/templates/post.php`

- [ ] **Step 1: Replace the file content**

Write the following to `site/templates/post.php`:

```php
<?php snippet('header') ?>

<article class="max-w-3xl mx-auto">
    <?php if ($image = $page->headerImage()): ?>
    <div class="border-2 border-pink overflow-hidden mb-8" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
        <img src="<?= $image->url() ?>" alt="<?= $page->title() ?>" class="w-full aspect-[21/9] max-h-[60vh] object-cover">
    </div>
    <?php endif ?>

    <h1 class="font-display text-4xl md:text-6xl uppercase text-text leading-none mb-3"><?= $page->title() ?></h1>

    <div class="flex flex-wrap items-center gap-3 font-body text-sm text-muted mb-8 border-b border-border pb-4">
        <?php if ($game = $page->parentGame()): ?>
        <span class="font-script text-xl md:text-2xl text-yellow">sobre <?= $game->title() ?></span>
        <?php endif ?>
        <?php if ($page->postDate()): ?>
        <span>• <?= $page->postDate() ?><?php if (preg_match('/^\d{4}/', (string)$page->postDate(), $m)): ?> <span class="text-cyan"><?= $m[0] ?></span><?php endif ?></span>
        <?php endif ?>
        <?php if ($page->author()->isNotEmpty()): ?>
        <span>• Por <span class="text-pink"><?= $page->author() ?></span></span>
        <?php endif ?>
    </div>

    <div class="font-body text-lg text-text leading-relaxed mb-8 [&>blockquote]:border-l-4 [&>blockquote]:border-pink [&>blockquote]:pl-4 [&>blockquote]:my-6 [&>blockquote]:font-script [&>blockquote]:text-2xl [&>h2]:font-display [&>h2]:uppercase [&>h2]:text-2xl [&>h2]:mt-8 [&>h2]:mb-3 [&>h3]:font-display [&>h3]:uppercase [&>h3]:text-xl [&>h3]:mt-6 [&>h3]:mb-2 [&_a]:text-pink [&_a]:underline [&_a]:decoration-pink/40 [&_a]:underline-offset-2 hover:[&_a]:text-yellow drop-cap">
        <?= $page->text()->kt() ?>
    </div>

    <?php $related = $page->relatedGames() ?>
    <?php if ($related->count() > 0): ?>
    <div class="border-t-2 border-pink pt-6 mt-8">
        <h3 class="font-display text-sm uppercase tracking-widest text-yellow mb-3">También aparece en</h3>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($related as $game): ?>
            <a href="<?= $game->url() ?>" class="font-body text-xs px-3 py-1 border-2 border-pink text-pink uppercase tracking-wider hover:bg-pink hover:text-bg transition">
                <?= $game->title() ?>
            </a>
            <?php endforeach ?>
        </div>
    </div>
    <?php endif ?>
</article>

<?php snippet('footer') ?>
```

- [ ] **Step 2: Verify the PHP parses**

Run:
```bash
php -l site/templates/post.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Boot dev server and visually confirm a post page**

Run:
```bash
bun run dev &
sleep 4
```

Open any post URL (e.g. `http://localhost:8888/games/{some-game-slug}/{some-post-slug}`). Confirm:
- Header image has pink border with glow
- Title in Anton uppercase
- Meta row has Caveat yellow "sobre [game]" + date with cyan year + author in pink
- Body has drop cap on first paragraph
- Headings inside the body use Anton uppercase
- Pull quotes (if any) have pink left border + Caveat font
- Related games chips have pink outlined style
- Footer renders

Stop the dev server.

- [ ] **Step 4: Commit**

```bash
git add site/templates/post.php
git commit -m "feat(template): redesign post page with drop cap, pink prose styles, Caveat meta"
```

---

## Task 16: Redesign games.php

**Files:**
- Modify: `site/templates/games.php`

- [ ] **Step 1: Replace the file content**

Write the following to `site/templates/games.php`:

```php
<?php snippet('header') ?>

<div class="flex items-end justify-between mb-6">
    <h1 class="font-display text-4xl md:text-6xl uppercase text-pink leading-none neon-glow-pink">Todos los juegos</h1>
    <?php snippet('script-accent', ['text' => 'explora el catálogo', 'color' => 'yellow', 'size' => 'md']) ?>
</div>

<?php
$bannerConfig = site()->alvAffBanners();
$hasEnabledPrograms = $bannerConfig['enabled'] && !empty(array_filter($bannerConfig['programs'], fn($p) => $p['enabled']));
?>
<?php $allGames = $page->children()->sortBy('title', 'asc'); ?>
<?php if ($allGames->count() > 0): ?>
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
    <?php
    $i = 0;
    foreach ($allGames as $game):
        $i++;
        snippet('game-card', ['game' => $game]);
        if ($hasEnabledPrograms && $i % 4 === 0):
            snippet('affiliate-banner', ['grid' => true, 'itemCount' => $i]);
        endif;
    endforeach;
    ?>
</div>
<?php else: ?>
<div class="border-2 border-pink p-8 text-center bg-bg" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
    <p class="font-graffiti text-3xl md:text-4xl text-pink uppercase neon-glow-pink">Próximamente</p>
    <?php snippet('script-accent', ['text' => 'vuelve pronto', 'color' => 'yellow', 'size' => 'md']) ?>
</div>
<?php endif ?>

<?php snippet('footer') ?>
```

- [ ] **Step 2: Verify the PHP parses**

Run:
```bash
php -l site/templates/games.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add site/templates/games.php
git commit -m "feat(template): redesign games grid with pink title and empty state"
```

---

## Task 17: Redesign genre.php

**Files:**
- Modify: `site/templates/genre.php`

- [ ] **Step 1: Replace the file content**

Write the following to `site/templates/genre.php`:

```php
<?php snippet('header') ?>

<?php
$genreSlug = $genreSlug ?? param('genre');
$allGames = $site->find('games')->children();
$games = $allGames->filter(function ($game) use ($genreSlug) {
    $gl = $game->genreList();
    return is_array($gl) && in_array($genreSlug, $gl);
})->sortBy('title', 'asc');

$bannerConfig = site()->alvAffBanners();
$hasEnabledPrograms = $bannerConfig['enabled'] && !empty(array_filter($bannerConfig['programs'], fn($p) => $p['enabled']));
$phrase = \GamePage::GENRE_PHRASES[$genreSlug] ?? null;
?>

<div class="text-center mb-6">
    <h1 class="font-display text-5xl md:text-7xl uppercase text-pink leading-none neon-glow-pink"><?= htmlspecialchars(urldecode($genreSlug)) ?></h1>
    <?php if ($phrase): ?>
        <?php snippet('script-accent', ['text' => $phrase, 'color' => 'yellow', 'size' => 'lg']) ?>
    <?php endif ?>
</div>

<?php snippet('marquee', ['phrase' => urldecode($genreSlug), 'color' => 'pink', 'bg' => 'black', 'speed' => 'medium']) ?>

<?php if ($games->count() > 0): ?>
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 mt-6">
    <?php
    $i = 0;
    foreach ($games as $game):
        $i++;
        snippet('game-card', ['game' => $game]);
        if ($hasEnabledPrograms && $i % 4 === 0):
            snippet('affiliate-banner', ['grid' => true, 'itemCount' => $i]);
        endif;
    endforeach;
    ?>
</div>
<?php else: ?>
<div class="border-2 border-pink p-8 text-center bg-bg mt-6" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
    <p class="font-graffiti text-3xl md:text-4xl text-pink uppercase neon-glow-pink">Próximamente</p>
    <?php snippet('script-accent', ['text' => 'vuelve pronto', 'color' => 'yellow', 'size' => 'md']) ?>
</div>
<?php endif ?>

<?php snippet('footer') ?>
```

- [ ] **Step 2: Verify the PHP parses**

Run:
```bash
php -l site/templates/genre.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add site/templates/genre.php
git commit -m "feat(template): redesign genre page with marquee, pink title, empty state"
```

---

## Task 18: Redesign search.php

**Files:**
- Modify: `site/templates/search.php`

- [ ] **Step 1: Replace the file content**

Write the following to `site/templates/search.php`:

```php
<?php snippet('header') ?>

<div class="max-w-3xl mx-auto">
    <div class="text-center mb-8">
        <h1 class="font-display text-4xl md:text-5xl uppercase text-pink leading-none neon-glow-pink">Buscar</h1>
        <?php snippet('script-accent', ['text' => 'encuentra tu próxima obsesión', 'color' => 'yellow', 'size' => 'md']) ?>
    </div>

    <form class="mb-8">
        <div class="flex gap-2">
            <input type="search" name="q" value="<?= html($query) ?>" placeholder="busca juegos, posts..." class="flex-1 bg-bg border-2 border-pink text-text font-display text-lg placeholder-muted px-4 py-3 focus:outline-none focus:border-yellow transition" style="box-shadow: 0 0 0 0 rgba(255,43,214,0);" onfocus="this.style.boxShadow='0 0 16px rgba(255,43,214,0.5)'" onblur="this.style.boxShadow='0 0 0 0 rgba(255,43,214,0)'">
            <button type="submit" class="border-2 border-pink text-pink font-display uppercase tracking-widest px-6 py-3 hover:bg-pink hover:text-bg transition neon-glow-pink">► Buscar</button>
        </div>
    </form>

    <?php if ($query): ?>
    <p class="font-body text-sm text-muted mb-4">
        <?php if ($results->count() > 0): ?>
            <?= $results->count() ?> resultado<?= $results->count() !== 1 ? 's' : '' ?> para "<?= html($query) ?>"
        <?php else: ?>
            Sin resultados para "<?= html($query) ?>"
        <?php endif ?>
    </p>

    <div class="space-y-4">
        <?php foreach ($results as $result): ?>
        <a href="<?= $result->url() ?>" class="block bg-bg border-2 border-pink hover:border-yellow p-4 transition" style="box-shadow: 0 0 12px rgba(255,43,214,0.3);">
            <div class="font-graffiti text-xs text-yellow uppercase tracking-widest mb-1"><?= $result->intendedTemplate()->name() === 'post' ? 'Post' : 'Game' ?></div>
            <h3 class="font-display text-lg uppercase text-text"><?= $result->title() ?></h3>
            <p class="font-body text-sm text-muted mt-1"><?= $result->summary()->excerpt(140) ?></p>
        </a>
        <?php endforeach ?>
    </div>

    <?php if (empty($results->count())): ?>
    <div class="border-2 border-pink p-8 text-center bg-bg mt-4" style="box-shadow: 0 0 16px rgba(255,43,214,0.4);">
        <p class="font-graffiti text-3xl text-pink uppercase neon-glow-pink">Sin resultados</p>
        <?php snippet('script-accent', ['text' => 'intenta otra cosa', 'color' => 'yellow', 'size' => 'md']) ?>
    </div>
    <?php endif ?>

    <?php if ($pagination && $pagination->pages() > 1): ?>
    <nav class="flex justify-center gap-2 mt-8">
        <?php if ($pagination->hasPrevPage()): ?>
        <a href="<?= $pagination->prevPageUrl() ?>" class="font-display text-sm uppercase tracking-widest px-4 py-2 border-2 border-pink text-pink hover:bg-pink hover:text-bg transition">← Anterior</a>
        <?php endif ?>
        <?php if ($pagination->hasNextPage()): ?>
        <a href="<?= $pagination->nextPageUrl() ?>" class="font-display text-sm uppercase tracking-widest px-4 py-2 border-2 border-pink text-pink hover:bg-pink hover:text-bg transition">Siguiente →</a>
        <?php endif ?>
    </nav>
    <?php endif ?>
    <?php endif ?>
</div>

<?php snippet('footer') ?>
```

- [ ] **Step 2: Verify the PHP parses**

Run:
```bash
php -l site/templates/search.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Commit**

```bash
git add site/templates/search.php
git commit -m "feat(template): redesign search page with pink form and result cards"
```

---

## Task 19: Redesign default.php (404 / fallback)

**Files:**
- Modify: `site/templates/default.php`

- [ ] **Step 1: Replace the file content**

Write the following to `site/templates/default.php`:

```php
<?php snippet('header') ?>

<div class="min-h-[60vh] flex flex-col items-center justify-center text-center px-4">
    <p class="font-graffiti text-7xl md:text-9xl text-pink uppercase neon-glow-pink leading-none">404</p>
    <?php snippet('script-accent', ['text' => 'no encontrado', 'color' => 'yellow', 'size' => 'lg']) ?>
    <p class="font-body text-muted mt-4 max-w-md">La página que buscas no existe o se ha movido. Vuelve al inicio para seguir explorando.</p>
    <a href="<?= url('/') ?>" class="mt-6 inline-block border-2 border-pink text-pink font-display text-base uppercase tracking-widest px-8 py-3 hover:bg-pink hover:text-bg transition neon-glow-pink">← Volver al inicio</a>
</div>

<?php snippet('footer') ?>
```

- [ ] **Step 2: Verify the PHP parses**

Run:
```bash
php -l site/templates/default.php
```

Expected: "No syntax errors detected".

- [ ] **Step 3: Boot dev server and confirm 404**

Run:
```bash
bun run dev &
sleep 4
```

Open `http://localhost:8888/this-page-does-not-exist`. Confirm:
- Large graffiti "404" in hot-pink with neon glow
- "no encontrado" in Caveat yellow
- "Volver al inicio" outlined pink button below

Stop the dev server.

- [ ] **Step 4: Commit**

```bash
git add site/templates/default.php
git commit -m "feat(template): redesign 404 page with graffiti type and pink CTA"
```

---

## Task 20: Build verification and final visual sweep

**Files:**
- No file changes; verification only.

- [ ] **Step 1: Production build**

Run:
```bash
bun run build
```

Expected: build succeeds with no errors. Output written to `public/assets/`. Note the new CSS file size (should be larger than before — Atomic Marker @font-face, marquee keyframes, neon glow utilities, etc.).

- [ ] **Step 2: Boot dev server and walk every page**

Run:
```bash
bun run dev &
sleep 4
```

Visit and visually verify:
- `/` — home: hero, marquee, alternating purple/pink genre bands, footer marquee
- `/games` — full games grid
- `/games/{any-game-slug}` — game page with cover, drop cap, info sidebar
- `/games/{any-game-slug}/{any-post-slug}` — post page with drop cap, pink prose
- `/genre/RPG` (or any genre) — genre page with marquee
- `/search?q=elden` (or any query) — search results
- `/this-doesnt-exist` — 404 page
- Resize browser to 375px — mobile menu works, layouts stack

If any of the above have visual issues, fix them inline (small adjustments to the relevant template/snippet) and commit the fix as a follow-up patch.

Stop the dev server.

- [ ] **Step 3: Verify Atomic Marker font loaded**

Open the home page, open DevTools → Network → filter by "font". Confirm:
- `atomic-marker.woff2` request returns 200
- `Anton`, `Caveat`, `Barlow+Condensed` requests to `fonts.googleapis.com` / `fonts.gstatic.com` return 200

If the Atomic Marker request returns 404, the `public/assets/fonts/atomic-marker.woff2` file is missing — go back to Task 1 and verify it was committed and is in the right path.

- [ ] **Step 4: Verify reduced motion behavior**

Open browser DevTools → Rendering → "Emulate CSS media feature prefers-reduced-motion" → `reduce`. Reload any page. Confirm:
- Marquee animation freezes (text is static, no scroll)
- Glow pulse on "Trending" label stops
- All hover transitions still work (no transition is removed; only animation)

- [ ] **Step 5: Final commit (if any fixups were made)**

If no fixups were made in step 2, skip this step.

```bash
git add -A
git commit -m "fix(templates): final visual sweep fixups"
```

- [ ] **Step 6: Summary**

Report to the user: build size, list of pages verified, and any follow-up notes (e.g. font fallback used if the original Atomic Marker URL was unreachable).
