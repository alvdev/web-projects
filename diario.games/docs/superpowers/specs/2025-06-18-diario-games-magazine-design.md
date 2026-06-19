# Diario.Games — Video Game Magazine Website

## Overview

A video game magazine website built with Kirby CMS 5, Tailwind CSS, and Vite. Features a retro neon dark mode design with magazine-style layout, search via SQLite full-text search, and SEO optimization.

## Tech Stack

| Component | Technology |
|-----------|------------|
| CMS | Kirby CMS 5 (PHP, flat-file pages) |
| Frontend CSS | Tailwind CSS (via Vite) |
| Asset bundler | Vite |
| Package manager | Bun |
| PHP dependencies | Composer |
| Database | SQLite (for search index + cross-game references) |
| Search | arnoson/loupe (SQLite FTS5 via Loupe engine) |
| Vite integration | arnoson/kirby-vite |
| SEO + Meta | tearoom1/meta-kit |
| Navigation | chrisbeluga/navigation |
| RSS Feeds | bnomei/feed |

## Data Model

### Games (Kirby Pages)

Stored as flat-file pages under `content/games/{game-slug}/`.

Fields:
- Title
- Slug
- Header/Cover image
- Release date
- Genre (multi-select from 12 categories)
- Developer
- Publisher
- Summary

### Posts (Kirby Pages, nested under games)

Stored as `content/games/{game-slug}/{post-slug}/`.

Fields:
- Title
- Slug
- Header image
- Summary
- Article body (Kirby textarea/editor)
- Release date (game's release date)
- Author
- Social share image
- Cross-game references (multi-select pages field linking to other games)
- Tags

### SQLite (via Loupe)

- Search index synced via Kirby hooks on save/delete
- Cross-game post relationships table (many-to-many)

## Menu Structure (Header)

12 genre categories:
1. Acción
2. Aventura
3. RPG
4. Shooter
5. Estrategia
6. Simulación
7. Deportes y Carreras
8. Terror
9. Puzzle
10. Supervivencia
11. Mundo abierto
12. Multijugador

## Color Palette: Cyberpunk Neon

| Role | Color | Hex |
|------|-------|-----|
| Background | Deep dark | `#0a0a12` |
| Surface | Dark navy | `#1a1a2e` |
| Surface alt | Deep blue | `#16213e` |
| Primary accent | Cyan | `#00f5ff` |
| Secondary accent | Magenta | `#ff00aa` |
| Accent green | Neon green | `#39ff14` |
| Body text | Off-white | `#e8e8f0` |
| Muted text | Gray | `#888` |

## Page Templates

### Homepage (`home.php`)

1. **Header** — Logo (logo.png) left, genre navigation center, search icon right
2. **Hero section** — Featured game (2/3 width) with large cover image + headline + summary. Trending sidebar (1/3 width) with latest stories list.
3. **Genre grid** — 2 columns per row, each column = one genre. Grid repeats for all 12 genres (6 rows total). Each genre section shows:
   - Genre subtitle with icon
   - 2 game cards side by side (thumbnail + game title + date)
4. **Footer** — About, Contact, Social links, copyright

### Game Page (`game.php`)

- Header cover image with title overlay
- Genre badges, release date, developer, publisher
- Summary paragraph
- Posts grid — 3 cards per row, each showing post header image + title + date

### Post Page (`post.php`)

- Header image
- Title, game reference, release date, author
- Social share buttons
- Article body
- Cross-game tags ("Also appears in: RPG, Mundo abierto")
- Related posts

### Search Page (`search.php`)

- Search input
- Results displayed as cards (header image + title + date + excerpt)
- Powered by arnoson/loupe (SQLite FTS)
- Pagination

### Category/Genre Page (`genre.php`)

- Genre title
- Games filtered by that genre
- Grid layout

## Project Structure

```
diario.games/
├── kirby/                    # Kirby CMS core (composer)
├── content/                  # Flat-file pages
│   └── games/
│       └── {game-slug}/
│           ├── game.txt
│           └── {post-slug}/
│               └── post.txt
├── site/
│   ├── blueprints/           # games.yml, posts.yml, site.yml, etc.
│   ├── controllers/          # search.php, etc.
│   ├── models/               # GamePage.php, PostPage.php
│   ├── plugins/              # Custom plugins (if needed)
│   ├── snippets/             # header.php, footer.php, game-card.php, etc.
│   └── templates/            # home.php, game.php, post.php, search.php, genre.php
├── assets/
│   └── src/
│       ├── js/               # Alpine.js or vanilla JS
│       ├── css/              # Tailwind input CSS
│       └── images/
├── public/
│   └── assets/               # Vite build output
├── sqlite/
│   └── diario.db             # SQLite database (Loupe index)
├── index.php                 # Kirby entry point
├── .htaccess
├── logo.png
├── vite.config.js
├── tailwind.config.js
├── postcss.config.js
├── package.json
├── composer.json
├── bun.lock
└── .gitignore
```

## Plugins (Kirby)

| Plugin | Purpose | URL |
|--------|---------|-----|
| arnoson/loupe | SQLite full-text search | https://plugins.getkirby.com/arnoson/loupe |
| arnoson/kirby-vite | Vite asset path helpers | https://plugins.getkirby.com/arnoson/kirby-vite |
| tearoom1/meta-kit | SEO, meta tags, OG, sitemap | https://plugins.getkirby.com/tearoom1/meta-kit |
| chrisbeluga/navigation | Menu builder field | https://plugins.getkirby.com/chrisbeluga/navigation |
| bnomei/feed | RSS/JSON feeds | https://plugins.getkirby.com/bnomei/feed |

## Implementation Notes

- No custom code if a plugin exists on plugins.getkirby.com
- Games are primary content type (flat-file pages)
- Posts are subpages of games (nested under game slug)
- Cross-game post references via Kirby's pages field (multi-select)
- Loupe auto-indexes content on page save/delete via hooks
- Logo file at `logo.png` in project root
- Tailwind config uses custom colors for the Cyberpunk palette
- Vite builds to `public/assets/`
