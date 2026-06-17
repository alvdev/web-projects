# SEO Local Rank

A free, open-source **local & international Google SERP checker** inspired by
[valentin.app](https://valentin.app). Pick a country + language, geocode any
address, and open a correctly localized Google search result page with the
right `gl`, `hl`, and `uule` parameters — without paying for a VPN.

## Stack

- **PHP 8.1+** front controller + tiny views (no framework, ~150 LOC of
  routing glue)
- **Tailwind CSS 3** compiled locally via the official CLI
- **Alpine.js 3** for the form interactions
- **Photon** (https://photon.komoot.io) for the address autocomplete &
  geocoding — OSM-based, **no API key, no signup, free**. Google Maps
  is **not** used anywhere on this site.
- Zero JS build step — `app.js` is hand-authored and loaded with `<script defer>`

## Setup

```bash
# 1. Install PHP dependencies
composer install --no-interaction

# 2. (Optional) Configure APP_URL if not on http://localhost:8000
cp .env.example .env

# 3. Install JS tooling and build the stylesheet + region bundle
npm install
npm run build

# 4. Serve the site
php -S localhost:8000 -t public
```

Open http://localhost:8000.

## Directory layout

```
.
├── bin/                     # Build scripts (PHP → JS regions bundle)
├── public/                  # Web root
│   ├── index.php            # Front controller + router
│   ├── .htaccess            # Apache rewrites
│   └── assets/
│       ├── css/app.css      # Compiled Tailwind output (gitignored)
│       └── js/
│           ├── app.js       # Alpine component & Maps integration
│           └── regions.generated.js  # Built by `bin/build-regions.php`
├── resources/css/app.css    # Tailwind source
├── src/
│   ├── Regions.php          # Country/language dataset
│   ├── Env.php              # Tiny .env loader
│   └── Views/
│       ├── layout.php       # HTML shell
│       └── home.php         # The form view
├── composer.json
├── package.json
├── tailwind.config.js
└── .env.example
```

## How it works

1. The user types (or selects) a **country + language**. The selection sets the
   form's `hl` (host language), `gl` (geolocation) and `action` (regional
   Google endpoint, e.g. `https://www.google.de/search`).
2. The user starts typing an **address** — the inline Photon typeahead suggests
   results from the OSM dataset. Pressing **Geocode** (or picking a suggestion)
   resolves the place to `(lat, lng)` and produces a `uule` string using the
   same encoding that `valentin.app` uses.
3. On submit, the JS builds a URL like
   `https://www.google.de/search?q=keto+rezepte&hl=de&gl=DE&uule=...` and
   opens it in a new tab.

## License

MIT
