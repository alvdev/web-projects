import { validateToken, refreshToken, TokenError } from "./token";
import { fetchAllPosts } from "./instagram";
import { queuePost } from "./index";
import { loadState, saveState } from "./state";
import { sendAlert } from "./mailer";

async function updateEnvToken(token: string): Promise<void> {
  const { readFile, writeFile } = await import("node:fs/promises");
  const { join } = await import("node:path");
  const envFile = join(import.meta.dirname, "..", "..", ".env");
  const content = await readFile(envFile, "utf8");
  const updated = content.replace(/^IG_ACCESS_TOKEN=.*$/m, `IG_ACCESS_TOKEN=${token}`);
  if (updated !== content) await writeFile(envFile, updated, "utf8");
}

async function ensureToken(state: Awaited<ReturnType<typeof loadState>>): Promise<string> {
  const token = process.env.IG_ACCESS_TOKEN ?? "";
  if (!token) throw new Error("IG_ACCESS_TOKEN not set");

  try {
    await validateToken();
  } catch (err) {
    if (err instanceof TokenError) {
      throw new Error("Instagram token invalid or expired. Regenerate it in the Meta Developer Console and update .env", { cause: err });
    }
    throw err;
  }

  const REFRESH_WINDOW_MS = 7 * 24 * 60 * 60 * 1000;
  if (state.tokenExpiresAt && state.tokenExpiresAt - Date.now() < REFRESH_WINDOW_MS) {
    console.log("[token] within 7-day window, refreshing...");
    const fresh = await refreshToken();
    await updateEnvToken(fresh);
    state.tokenExpiresAt = Date.now() + 60 * 24 * 60 * 60 * 1000;
  } else if (!state.tokenExpiresAt) {
    state.tokenExpiresAt = Date.now() + 60 * 24 * 60 * 60 * 1000;
  }

  return token;
}

async function main(): Promise<void> {
  console.log(`=== instagram-to-blog hourly watcher ===`);

  try {
    const state = await loadState();
    const token = await ensureToken(state);
    await saveState(state);

    // Only look for truly new posts once the original backlog is fully published
    if (!state.backlogDone) {
      console.log("[watcher] backlog still in progress, skipping");
      return;
    }

    if (state.pending.length > 0) {
      console.log(`[watcher] ${state.pending.length} post(s) awaiting approval, skipping`);
      return;
    }

    const posts = await fetchAllPosts(token);
    if (posts.length === 0) {
      console.log("[watcher] no posts fetched");
      return;
    }

    // Track the newest post ID ever seen; initialize silently on first run
    const latest = posts[0].id;
    const prevHighest = state.highestSeenId;

    if (!prevHighest) {
      state.highestSeenId = latest;
      await saveState(state);
      console.log(`[watcher] initialized highestSeenId=${latest}`);
      return;
    }

    // Truly new post(s): newer than the highest previously seen
    const newest = posts.filter((p) => BigInt(p.id) > BigInt(prevHighest));

    if (newest.length === 0) {
      console.log("[watcher] no truly new posts");
      return;
    }

    state.highestSeenId = latest;
    console.log(`[watcher] ${newest.length} truly new post(s) found`);
    const toProcess = newest.reverse().slice(0, Number(process.env.MAX_POSTS_PER_RUN ?? 1));

    for (const post of toProcess) {
      const entry = await queuePost(
        {
          id: post.id,
          caption: post.caption,
          mediaUrl: post.media_url ?? post.thumbnail_url ?? "",
          timestamp: post.timestamp,
          mediaType: post.media_type,
        },
        state,
        { notify: true },
      );
      console.log(`[watcher] queued new post ${post.id} -> ${entry.prepared.mdxPath}`);
    }

    await saveState(state);
  } catch (err) {
    console.error("[watcher] fatal:", err);
    await sendAlert("[Urban Sync] Watcher FATAL error", (err as Error).message);
    process.exitCode = 1;
  }
}

if (import.meta.main) {
  await main();
}
