# alv-releases

Upcoming video game releases for diario.games, sourced from the IGDB API.

## What it provides

- `site()->releases()` — `Alv\Releases\Releases` service
- `/lanzamientos` — releases calendar page (tabs: Por fecha / Más esperados)
- `home-upcoming-releases` snippet — homepage module
- `POST /lanzamientos-warm?key=...` — refreshes the cached dataset

## Options

| Option | Env | Default | Description |
|---|---|---|---|
| `alv.releases.cache-ttl` | `RELEASES_CACHE_TTL` | `21600` | Dataset cache TTL in seconds |
| `alv.releases.warm-key` | `RELEASES_WARM_KEY` | falls back to `STEAM_STATS_WARM_KEY` | Key required by the warm route |
| `alv.releases.notable-hypes` | `RELEASES_NOTABLE_HYPES` | `20` | Minimum `hypes` for a game to appear in "Próximos lanzamientos" |
| `alv.releases.fixture-file` | `RELEASES_FIXTURE` | `''` | JSON fixture that bypasses IGDB (used by tests) |

## Cron

Warm the cache every 6 hours:

```bash
php scripts/collect-releases.php
```

or, when HTTP cron is preferred:

```bash
curl -X POST "https://diario.games/lanzamientos-warm?key=$RELEASES_WARM_KEY"
```

## Data

Three IGDB queries are cached together (note: `category` is `null` for main games
in IGDB, so the queries use `(category = 0 | category = null)`):

- **Recién lanzados**: released in the last 30 days, sorted by `hypes desc`
- **Próximos lanzamientos**: future releases with `hypes >= alv.releases.notable-hypes`,
  sorted by date asc, excluding games shown in "Más esperados"
- **Más esperados**: future releases with `hypes > 0`, sorted by `hypes desc`

The homepage renders the three lists as columns. `/lanzamientos` renders the three
lists as sections with genre filter chips (client-side, like `/games`).

Unreleased games are never linked to internal pages. Released games link to
their diario.games page or fall back to `/games/by-igdb-id/{id}`.

Attribution ("Datos de IGDB") is rendered on `/lanzamientos` as required by IGDB.
