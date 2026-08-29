#!/usr/bin/env bash
# kv55 production update: pull latest code, refresh deps/bot/systemd, and
# remind when the local .env is out of date. Run manually after pushing from
# the dev machine:  bash ~/dev/urban/urbanstyle/astro/scripts/instagram-to-blog/update.sh
set -e

ASTRO=/home/alvdev/dev/urban/urbanstyle/astro
REPO=/home/alvdev/dev/urban
BUN=/home/alvdev/.bun/bin/bun

echo "=== pulling $REPO ==="
BEFORE=$(git -C "$REPO" rev-parse HEAD)
git -C "$REPO" pull --ff-only
AFTER=$(git -C "$REPO" rev-parse HEAD)

CHANGED=""
if [ "$BEFORE" != "$AFTER" ]; then
  CHANGED=$(git -C "$REPO" diff --name-only "$BEFORE" "$AFTER")
fi

# If the pull replaced update.sh itself, re-exec the new version — bash may
# have read the old file incrementally and would otherwise keep running stale
# logic (e.g. wrong bot-restart list).
if echo "$CHANGED" | grep -q "urbanstyle/astro/scripts/instagram-to-blog/update.sh"; then
  echo "=== update.sh changed — re-running new version ==="
  exec bash "$0"
fi

if echo "$CHANGED" | grep -q "urbanstyle/astro/bun.lock"; then
  echo "=== bun install (lockfile changed) ==="
  (cd "$ASTRO" && "$BUN" install)
fi

if echo "$CHANGED" | grep -q "urbanstyle/astro/scripts/instagram-to-blog/systemd/"; then
  echo "=== systemd units changed — reinstalling ==="
  cp "$ASTRO"/scripts/instagram-to-blog/systemd/*.{service,timer} ~/.config/systemd/user/
  systemctl --user daemon-reload
  systemctl --user restart instagram-bot.service
elif echo "$CHANGED" | grep -q "urbanstyle/astro/scripts/instagram-to-blog/"; then
  echo "=== pipeline code changed — restarting bot ==="
  systemctl --user restart instagram-bot.service
fi

echo "=== .env freshness check ==="
ENV_FILE="$ASTRO/.env"
LAST_PULL=$(stat -c %Y "$REPO/.git/FETCH_HEAD" 2>/dev/null || echo 0)
ENV_MTIME=$(stat -c %Y "$ENV_FILE" 2>/dev/null || echo 0)
if [ "$ENV_MTIME" -lt "$LAST_PULL" ]; then
  echo "⚠️  kv55 .env is older than the latest code pull — new env vars may be missing."
fi
MISSING=0
for key in $(grep -oE '^[A-Z_]+=' "$ASTRO/scripts/instagram-to-blog/.env.example" | tr -d '='); do
  if ! grep -qE "^$key=" "$ENV_FILE"; then
    echo "  ➜ MISSING: $key"
    MISSING=1
  fi
done
if [ "$MISSING" = 1 ]; then
  echo "  ➜ From the DEV machine: scp .env alvdev@kv55.local:$ENV_FILE"
  echo "  ➜ Then on kv55 keep NODE_BIN_DIR=/home/alvdev/.bun/bin (build needs bun on PATH)"
fi

echo "=== done ==="