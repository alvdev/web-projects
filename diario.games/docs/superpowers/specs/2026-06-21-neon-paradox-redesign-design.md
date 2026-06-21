# Diario.Games — Neon Paradox Visual Redesign

## Overview

Full visual overhaul of the Kirby templates and snippets, replacing the current "clean cyberpunk magazine" look with a band-poster, graffiti-tinged, hot-pink-dominated cyberpunk look inspired by the Neon Paradox Dribbble concept. The data model, page structure, and content remain unchanged; only the visual layer (CSS, fonts, layout treatment) is replaced.

The new look keeps magazine credibility (readable body type, structured genre browsing, search) while pulling in Neon Paradox's energy: full-bleed colored section blocks, hot-pink and electric-purple dominant palette, graffiti display fonts, neon glow on text, marquee scroll strips, italic script accents, and sharp square corners.

## Goals

- Replace the "bordered card grid" feel with a more immersive band-poster / magazine-cover feel
- Make each genre section feel like a distinct, colored "track" instead of a stacked list of small cards
- Add the marquee scroll strip and graffiti logo treatment as signature brand elements
- Preserve all current functionality (search, genre filtering, affiliate banners, meta-kit SEO)
- Improve brand distinctiveness without sacrificing readability

## Non-Goals

- No new content types or page templates
- No new Kirby fields or blueprints (no schema changes)
- No JavaScript framework additions (vanilla CSS animations only)
- No light mode (dark-only)
- No new plugins
- No backend / PHP logic changes beyond a static genre-phrase map

## Design Tokens

### Color Palette

| Token | Hex | Role |
|---|---|---|
| `--color-bg` | `#0a0a12` | Page background |
| `--color-surface` | `#1a1a2e` | Cards, panels (replaces current `--color-surface`) |
| `--color-surface-alt` | `#2a2a4e` | Inset elements, code blocks |
| `--color-pink` | `#ff2bd6` | Primary accent — buttons, borders, links, display text (replaces cyan as primary) |
| `--color-purple` | `#6b1aff` | Secondary — full-bleed genre bands, banner (new) |
| `--color-yellow` | `#f0ff00` | Tertiary — "live" pulse, script accent, marquee (new) |
| `--color-cyan` | `#00f5ff` | Retained — cool counter-accent on dates, year tags (de-emphasized) |
| `--color-green` | `#39ff14` | Retained — "Trending" label, success state (de-emphasized) |
| `--color-text` | `#f5f5f5` | Body text |
| `--color-muted` | `#8888a8` | Muted text |
| `--color-border` | `#3a3a5e` | Default borders |

The existing Tailwind v4 `@theme` block in `assets/src/css/app.css` is replaced with the new tokens. The current `neon-cyan` / `neon-magenta` / `neon-green` classes are aliased to the new tokens for backward compatibility: `neon-cyan` → `var(--color-cyan)`, `neon-magenta` → `var(--color-pink)` (closest hue match), `neon-green` → `var(--color-green)`. The body element's `bg-dark` class is replaced with `bg-bg` (using the new `--color-bg` token), or `--color-dark` is kept as an alias for `--color-bg` so the existing `bg-dark` class on the body element still resolves.

### Typography

| Role | Font | Source | Where used |
|---|---|---|---|
| Display (h1, h2, eyebrows) | `Anton` | Google Fonts | All headings, page titles, section titles, button text, eyebrow tags |
| Script accent | `Caveat` | Google Fonts | 1-2 italic moments per section ("sumérgete", "llega ahora", "explora el catálogo") |
| Body (paragraphs, lists, captions) | `Barlow Condensed` 400/600/800 | Google Fonts | Body text, captions, meta info, list items, navigation |
| Graffiti (logo, marquee) | `Atomic Marker` (Set Sail Studios) | Self-hosted woff2 | Site logo wordmark, marquee strip, large 404, "PRÓXIMAMENTE" empty states |

Google Fonts loaded via `<link rel="preconnect">` + `<link rel="stylesheet">` in `header.php` head. Atomic Marker is downloaded once, converted to `.woff2` (using `woff2_compress` or similar), placed at `public/assets/fonts/atomic-marker.woff2`, and declared via `@font-face` in `app.css` with `font-display: swap`. If the source URL is unreachable, fall back to `Bungee` from Google Fonts (visually similar blocky display) and document the swap.

### Effects

- **Neon glow pink**: `text-shadow: 0 0 8px var(--color-pink), 0 0 24px rgba(255,43,214,0.6)` on primary display text, buttons, and active links
- **Neon glow yellow**: same pattern with yellow color
- **Grain overlay**: subtle SVG noise (or repeating-gradient approximation) as a `::before` pseudo-element on the body, 4% opacity, fixed, `pointer-events: none`, `z-index: 0`
- **Marquee animation**: pure CSS `@keyframes` translating `-50%` over a 2x track, `animation-timing-function: linear`, `animation-iteration-count: infinite`
- **Glow pulse**: 2.4s ease-in-out keyframe animating text-shadow intensity — applied to the "Trending" label and any "Live" indicators
- **Drop cap**: first paragraph of `post.php` and `game.php` summary uses `::first-letter` with `Atomic Marker`, `font-size: 4em`, `float: left`, hot-pink color
- **Reduced motion**: `@media (prefers-reduced-motion: reduce)` disables marquee animation, glow pulse, and any transition; content stays static

### Tailwind Configuration

`assets/src/css/app.css` `@theme` block is rewritten with the new color tokens and font families. New `@utility` classes added (Tailwind v4 syntax used in the existing `app.css` `debug-screens` block):

- `@utility neon-glow-pink`, `@utility neon-glow-yellow`, `@utility neon-glow-cyan` — text-shadow effects
- `@utility grain` — fixed noise overlay pseudo-element
- `@utility marquee` and `@utility marquee-track` — flex container + animated child
- `@utility glow-pulse` — looping text-shadow animation
- `@utility drop-cap` — `::first-letter` styling helper
- `@utility band-purple`, `@utility band-pink` — full-bleed section background utilities (sets `bg-[var(--color-purple)]` / `bg-[var(--color-pink)]`, `text-dark`, `py-8`)

Font families registered via `@theme { --font-display: "Anton", "Impact", sans-serif; --font-script: "Caveat", cursive; --font-body: "Barlow Condensed", sans-serif; --font-graffiti: "Atomic Marker", "Bungee", cursive; }` so they're available as `font-display`, `font-script`, `font-body`, `font-graffiti` Tailwind classes.

## Snippets

### `header.php`

- Solid `#0a0a12` background, 2px hot-pink bottom border with `neon-glow-pink`
- Logo: `font-graffiti text-3xl md:text-4xl` with letter-spacing, "DIARIO" hot-pink + ".GAMES" yellow
- Genre nav: `font-body font-semibold uppercase tracking-widest text-sm` — active item gets 2px hot-pink underline; hover → text-pink 100ms
- Search icon: hot-pink, becomes yellow on hover
- Sticky: `sticky top-0 z-50` (unchanged)
- Google Fonts `<link>` tags added to `<head>`

### `footer.php`

- `#0a0a12` background with grain
- 2px hot-pink top border
- 3-column desktop layout: logo (small, font-graffiti, hot-pink) | nav links (uppercase, muted) | social icons (hot-pink SVGs)
- Bottom bar: copyright + Terms/Privacy in muted text

### `hero.php`

- `grid-cols-3` desktop, featured takes 2/3
- Featured card: black surface, 2px hot-pink border with `neon-glow-pink`
  - Headline: `font-display text-5xl md:text-7xl uppercase` white
  - Caveat accent line below: hot-pink, rotating phrase from a 4-6 entry static array
  - Eyebrow tag (e.g. "Último post", "Featured"): rotated -2deg, hot-pink or yellow background, `font-display text-xs` black, absolute top-right
  - CTA: outlined `font-display text-sm` button with `► LEER` text
- Trending card: black surface, hot-pink border, "TRENDING" eyebrow in `font-display text-xs` yellow with `glow-pulse`, list of game titles in `font-body font-semibold` separated by 1px pink/20 borders
- Empty fallback: `font-graffiti text-4xl` "PRÓXIMAMENTE" centered card

### `game-card.php`

- Black background, 2px hot-pink border, **no border-radius** (sharp corners)
- Cover: `aspect-[16/9]`, full-bleed, group hover scales 1.05 over 300ms, border brightness increases
- Body p-3: `font-display text-sm md:text-base uppercase` white title (line-clamp-2)
- Meta: `font-body text-xs` muted, with hot-pink genre label inline (no pill background)
- No-image fallback: `bg-surface-alt aspect-video` with `font-graffiti text-xs` "NO COVER" in pink

### `post-card.php`

- Same structure as `game-card.php` (unified into the hot-pink accent system)
- Game reference line: `font-script text-lg` yellow, format "sobre [game title]"
- Date: `font-body text-xs` muted, year in cyan counter-accent

### `genre-nav.php`

- Reuse header treatment styling
- Horizontal scroll with `scrollbar-hide`, no visible scrollbar
- Active state: 2px hot-pink underline

### `marquee.php` (new)

- Params: `phrase` (string, default "ÚLTIMOS POSTS"), `color` ("pink" | "yellow" | "purple", default "pink"), `bg` (color name or "transparent", default "black"), `speed` ("slow" | "medium" | "fast", default "medium")
- Renders a flex container with 2 copies of the phrase for seamless loop
- `font-graffiti text-3xl md:text-5xl uppercase` text, neon glow applied
- CSS-only animation, speed maps to `animation-duration`: slow=30s, medium=18s, fast=10s
- Respects `prefers-reduced-motion: reduce`

### `script-accent.php` (new)

- Params: `text` (string, required), `color` ("pink" | "yellow", default "pink"), `size` ("sm" | "md" | "lg", default "md")
- Renders `font-script` text with neon glow
- Used 1-2x per section as a personality moment

## Page Templates

### `home.php`

Structure:
1. `header.php`
2. `hero.php`
3. `marquee.php` — "ÚLTIMOS POSTS" pink-on-black, full-bleed
4. **Genre bands** — loop through `$genreGames` (12 genres, grouped into 6 bands alternating colors):
   - Band 1: purple bg → "ACCIÓN" + "AVENTURA" (2 cols)
   - Band 2: pink bg → "RPG" + "SHOOTER" (2 cols)
   - Band 3: purple → "ESTRATEGIA" + "SIMULACIÓN" (2 cols)
   - Band 4: pink → "DEPORTES Y CARRERAS" + "TERROR" (2 cols)
   - Band 5: purple → "PUZZLE" + "SUPERVIVENCIA" (2 cols)
   - Band 6: pink → "MUNDO ABIERTO" + "MULTIJUGADOR" (2 cols)
   - Each band uses the `band-purple` or `band-pink` utility, `text-dark`, and contains 2 genre sections in a `grid-cols-1 md:grid-cols-2` layout (stacks on mobile, 2-up on desktop). Each genre section: `font-display text-5xl md:text-7xl uppercase` title + `script-accent` snippet with genre-specific phrase from the map + 3 `game-card.php` in a row + outlined "► VER [GENRE]" button
5. `marquee.php` (second placement, optional different phrase)
6. `affiliate-banner.php` integrated with hot-pink dashed border + "PUBLICIDAD" Caveat eyebrow
7. `footer.php`

Genre phrase map (defined in `site/models/GamePage.php` as a `public const GENRE_PHRASES`):

```php
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
```

### `game.php`

- **Header cover**: full-bleed `aspect-[21/9]` cover with gradient overlay. Title in `font-display text-4xl md:text-6xl uppercase` white with `neon-glow-pink` shadow
- **Genre pills / tags / release date** overlaid on cover — pills use hot-pink outlined style (1px border, transparent fill, hot-pink text)
- **Info row** (3 cols desktop):
  - Left: summary, `font-body text-lg`, with `drop-cap` on first letter (`Atomic Marker text-pink float-left text-7xl mr-2`)
  - Middle: meta sidebar (dev, publisher, release date, platforms) — hot-pink label / muted value pattern in `font-body`
  - Right: genre + tag list, hot-pink text on black card with `bg-surface`
- **Screenshots**: gallery grid, thumbs get 2px hot-pink border on hover
- **Posts grid**: `font-display text-3xl uppercase` "POSTS SOBRE [GAME]" + Caveat accent, `post-card` 3-column grid

### `post.php`

- **Header image**: full-bleed, capped at `aspect-[21/9] max-h-[60vh]`
- **Title block**: `font-display text-4xl md:text-6xl` white, meta row (game ref in `font-script` yellow, date + author in `font-body` muted with cyan year)
- **Body**: `font-body text-lg` for paragraphs, `font-display uppercase` for h2/h3, hot-pink `a` links, drop cap on first paragraph if `$page->text()->isNotEmpty()`
- **Pull quotes** (`<blockquote>`): hot-pink 4px left border, `font-script text-2xl` accent
- **Related games**: `font-display text-sm uppercase` eyebrow " TAMBIÉN APARECE EN ", hot-pink outlined pill chips

### `games.php`

- Title: `font-display text-5xl uppercase` "TODOS LOS JUEGOS" + `script-accent` "explora el catálogo"
- Grid: `grid-cols-2 md:grid-cols-3 lg:grid-cols-4` of `game-card.php`
- Affiliate banner inserted every 4 cards (same pattern as current)
- Pagination: "← ANTERIOR" / "SIGUIENTE →" with hot-pink border buttons

### `genre.php`

- Title: `font-display text-6xl uppercase` genre name in hot-pink with `neon-glow-pink`
- Caveat phrase from `GENRE_PHRASES` map for the current genre
- `marquee.php` strip with the genre name in pink-on-black
- Grid: 4-col desktop of `game-card.php`
- Affiliate banner insertion every 4 cards
- Empty state: hot-pink outlined card, `font-graffiti text-3xl` "PRÓXIMAMENTE" + `font-script` "vuelve pronto"

### `search.php`

- Heading: `font-display text-4xl` "BUSCAR" + `script-accent` "encuentra tu próxima obsesión"
- Form: input with `font-display text-lg` placeholder, hot-pink outlined submit "► BUSCAR". Input gets hot-pink border + `neon-glow` on focus
- Results: list of cards with `font-graffiti text-xs` "POST" or "GAME" eyebrow in yellow + `font-display uppercase` title + `font-body` summary. Hot-pink border on hover
- Pagination: same as `games.php`
- No-results state: hot-pink outlined card with `font-graffiti` "SIN RESULTADOS" + `font-script` "intenta otra cosa"

### `default.php` (404 / fallback)

- Full-bleed `#0a0a12` with grain
- Centered: `font-graffiti text-7xl md:text-9xl neon-glow-pink` "404"
- `font-script text-3xl neon-glow-yellow` "no encontrado"
- `font-display uppercase` outlined "← VOLVER AL INICIO" hot-pink button

## Data Flow

- No new Kirby fields, blueprints, or page models are introduced. The existing data layer is unchanged.
- `GENRE_PHRASES` constant lives on `site/models/GamePage.php` (or `site/config/config.php` if the model file doesn't exist yet — verified during implementation). It's read by `home.php` and `genre.php`.
- Hero accent phrases: static 4-6 entry array in `site/config/config.php` under `heroAccentPhrases`, read by `hero.php`.
- Marquee and script-accent snippets are stateless; they accept params only.
- Affiliate banner snippet integration: same controller behavior, new visual treatment in the snippet itself.

## Error Handling

- **No cover / hero image**: `game-card.php`, `post-card.php`, and `game.php` cover fall back to a hot-pink-to-purple gradient with the title in `font-graffiti` overlaid.
- **No games found** (genre page): restyled empty state (see `genre.php`).
- **No search results**: restyled empty state (see `search.php`).
- **Hero finds no posts and no games**: render `font-graffiti text-4xl` "PRÓXIMAMENTE" card.
- **404**: `default.php` template handles with the graffiti "404" treatment.

## Accessibility

- Neon glow effects are decorative; underlying text contrast (white-on-black, hot-pink-on-black, dark-on-pink) meets WCAG AA.
- `Atomic Marker` is used only for short logos, marquee, and "PRÓXIMAMENTE" / "404" words (< 12 chars per word). Body content stays in `Barlow Condensed` for readability.
- `Caveat` script is decorative accent; no critical content uses it.
- Marquee animation disabled under `prefers-reduced-motion: reduce`.
- All interactive elements remain keyboard-accessible (no custom controls added).
- Color is not the sole signal — genre pills, status labels, and links have text labels and structural cues (border, position) in addition to color.

## Caching

- Vite rebuilds the CSS bundle; Tailwind v4 purges unused classes. Build output: `public/assets/`.
- Atomic Marker: downloaded once, converted to `.woff2`, placed at `public/assets/fonts/atomic-marker.woff2`. Loaded via `@font-face` with `font-display: swap`. Cached by browser with `Cache-Control` from Kirby's static handler.
- Google Fonts (Anton, Caveat, Barlow Condensed): loaded via `<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>` and `<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=...">` in `header.php`. Browser-cached per Google Fonts CDN.
- No server-side caching changes.

## Testing

### Visual (manual)

- Render each template (`home`, `game`, `post`, `games`, `genre`, `search`, `default`) and confirm:
  - Headings use `font-display` (Anton), uppercase
  - Genres render as alternating purple / pink full-bleed bands
  - Marquee scrolls smoothly on homepage, between bands, and on genre page
  - Drop cap renders on first paragraph of `post.php` and `game.php` summary
  - 404 default page shows graffiti "404" with hot-pink glow
  - Affiliate banner renders inside the new band layout (or suppresses if no enabled programs)
- Test responsive breakpoints: mobile (375px), tablet (768px), desktop (1280px), wide (1920px)
- Confirm Atomic Marker logo renders in the graffiti font (not fallback) in DevTools Network tab
- Test `prefers-reduced-motion: reduce` — marquee freezes, glow pulse stops

### Functional

- Search: query "elden" returns Elden Ring games + posts; pagination works
- Genre filter: clicking each genre nav link loads the right page
- Game page: clicking a game card navigates to its `game.php`
- Post page: clicking a post card navigates to its `post.php`; "related games" pills work
- Meta kit: OG image, title, description still emit correctly
- Mobile menu opens/closes (existing functionality preserved)

### Build & Runtime

- `bun run build` succeeds — Vite + Tailwind v4 compiles, no missing-class warnings
- `bun run dev` boots the Vite + PHP dev server
- No PHP errors in the Kirby log (`storage/logs/`)
- No 404s on `/assets/fonts/atomic-marker.woff2` or Google Fonts requests

### Browser Compatibility

- Chrome, Firefox, Safari latest
- Marquee animation is GPU-accelerated (uses `transform`)
- `@font-face` Atomic Marker loads on first render; FOUT acceptable for the logo only

## Implementation Notes

- The existing Tailwind v4 `@theme` block in `assets/src/css/app.css` is replaced with the new tokens. Old `neon-cyan` / `neon-magenta` / `neon-green` classes are aliased to the new tokens for backward compatibility — anything still using them keeps working.
- Tailwind v4's `@theme` directive handles font registration; no `tailwind.config.js` exists or is needed (project doesn't have one).
- The Atomic Marker font is downloaded once during implementation from the URL provided by the user, converted to `.woff2` using `woff2_compress` (or similar tool), and committed to `public/assets/fonts/`. If conversion tooling is unavailable, the `.otf` is committed as-is (slightly larger payload, same visual result).
- All snippet changes are isolated — `snippet('header')`, `snippet('footer')`, etc. continue to work as drop-in replacements for the current snippets. No controller changes are required.
- Genre-phrase map is added to `site/models/GamePage.php` (verify file exists first; create as a class extending `Kirby\Cms\Page` if not). If the model file doesn't exist, the map is added to `site/config/config.php` under a `diario` namespace key and accessed via `kirby()->option('diario.genrePhrases')`.

## File Changes Summary

### Files to modify

- `site/snippets/header.php` — new look, Google Fonts links, Atomic Marker logo
- `site/snippets/footer.php` — new look
- `site/snippets/hero.php` — new look, hero accent phrases
- `site/snippets/game-card.php` — new look, no border-radius, hot-pink border
- `site/snippets/post-card.php` — new look, unified hot-pink accent
- `site/snippets/genre-nav.php` — new look
- `site/templates/home.php` — new band layout, marquee insertion, genre bands
- `site/templates/game.php` — new cover, drop cap, info row
- `site/templates/post.php` — new header, drop cap, prose styles
- `site/templates/games.php` — new title, grid treatment
- `site/templates/genre.php` — new title, marquee, empty state
- `site/templates/search.php` — new form, results card, empty state
- `site/templates/default.php` — new 404 look
- `assets/src/css/app.css` — replace `@theme` block, add new utility classes, add `@font-face` for Atomic Marker

### Files to create

- `site/snippets/marquee.php` — new snippet
- `site/snippets/script-accent.php` — new snippet
- `public/assets/fonts/atomic-marker.woff2` — downloaded + converted font

### Files to verify (may need creation or modification)

- `site/models/GamePage.php` — add `GENRE_PHRASES` constant if file exists; else add to config
- `site/config/config.php` — add `heroAccentPhrases` option (and `genrePhrases` fallback if model file is missing)
