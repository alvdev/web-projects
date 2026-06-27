# Roman Numerals in Slugs

## Problem

IGDB provides slugs with roman numerals (e.g., `street-fighter-iii-3rd-strike`). These slugs are used directly as directory names under `content/games/`, meaning the page slug contains roman numerals. Slugs should use digits (e.g., `street-fighter-3-3rd-strike`).

## Solution

### 1. New `romanToDigits()` function in `helpers.php`

Convert all common roman numerals (I–XVI) to digits in a slug using word-boundary regex replacements, ordered longest-first to avoid partial matches.

### 2. Normalize in `GameImporter::import()`

After extracting the IGDB slug, normalize it with `romanToDigits()` before using it as the directory name. Also check if the unnormalized IGDB slug exists as a directory (legacy content) and migrate it by renaming the directory.

### 3. Route redirect in `config.php`

When a page is resolved by the `games/(:any)` route but its slug contains roman numerals, rename the directory to the digit version and 301 redirect to the canonical URL.

### 4. Update `SteamStatsDB::normalizeSlug()`

Expand the existing roman numeral handling to match the new `romanToDigits()` function for consistency.

## Files

| File | Change |
|------|--------|
| `site/plugins/alv-igdb/classes/helpers.php` | Add `romanToDigits()` function |
| `site/plugins/alv-igdb/classes/GameImporter.php` | Use `romanToDigits()` on imported slug, migrate legacy dirs |
| `site/config/config.php` | Add redirect from roman numeral slug to digit slug |
| `site/plugins/alv-steam-stats/classes/SteamStatsDB.php` | Update `normalizeSlug()` to match |
