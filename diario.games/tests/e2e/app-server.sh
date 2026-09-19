#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
TMP="$ROOT/tests/.tmp/e2e"

rm -rf "$TMP"
mkdir -p "$TMP/content" "$TMP/cache" "$TMP/media" "$TMP/sessions" "$TMP/accounts"

php "$ROOT/tests/e2e/seed-content.php" "$TMP/content"

E2E_DB="$TMP/steam_stats.db"
STEAM_STATS_DB_PATH="$E2E_DB" php "$ROOT/tests/e2e/seed-db.php"

# Kirby resolves plugin/config paths through these symlinks; public/assets are
# copied because the router only serves static files whose realpath is inside
# the document root.
ln -s "$ROOT/kirby" "$TMP/kirby"
ln -s "$ROOT/site" "$TMP/site"
cp -r "$ROOT/public" "$TMP/public"
cp -r "$ROOT/assets" "$TMP/assets"
cp "$ROOT/tests/e2e/index.php" "$TMP/index.php"

cd "$ROOT"
exec env \
  STEAM_STATS_DB_PATH="$E2E_DB" \
  DIARIO_DISABLE_PRICES=1 \
  php -S 127.0.0.1:8898 -t "$TMP" "$ROOT/kirby/router.php"
