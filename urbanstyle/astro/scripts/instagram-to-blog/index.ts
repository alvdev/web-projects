import { validateToken, refreshToken, TokenError } from "./token";
import { fetchAllPosts, filterNewPosts } from "./instagram";
import { generateArticles } from "./llm";
import { preparePost } from "./content";
import { loadState, saveState } from "./state";
import { sendAlert } from "./mailer";
import { notifyTelegram } from "./telegram";
import type { NewPost, PendingEntry } from "./types";

const MAX_POSTS_PER_RUN = Number(process.env.MAX_POSTS_PER_RUN ?? 1);

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
  const needsRefresh = state.tokenExpiresAt && state.tokenExpiresAt - Date.now() < REFRESH_WINDOW_MS;

  if (needsRefresh) {
    console.log("[token] within 7-day window, refreshing...");
    const fresh = await refreshToken();
    await updateEnvToken(fresh);
    state.tokenExpiresAt = Date.now() + 60 * 24 * 60 * 60 * 1000;
    console.log("[token] refreshed, new 60-day window");
  } else if (!state.tokenExpiresAt) {
    state.tokenExpiresAt = Date.now() + 60 * 24 * 60 * 60 * 1000;
  }

  return token;
}

export async function queuePost(
  post: NewPost,
  state: Awaited<ReturnType<typeof loadState>>,
  options: { notify?: boolean; feedback?: string; entryId?: string } = {},
): Promise<PendingEntry> {
  const articles = await generateArticles(post, options.feedback);

  // Primary article for preview: prefer Gemini (primary provider), fallback DeepSeek
  const primaryArticle = articles.gemini ?? articles.deepseek!;
  const prepared = preparePost(primaryArticle, post);

  let entry = state.pending.find((e) => e.id === (options.entryId ?? post.id));
  if (entry) {
    entry.article = primaryArticle;
    entry.prepared = prepared;
    entry.articles = articles;
    entry.feedback = null;
    entry.status = "pending";
    entry.attempts += 1;
    entry.createdAt = new Date().toISOString();
  } else {
    entry = {
      id: post.id,
      article: primaryArticle,
      post,
      prepared,
      articles,
      status: "pending",
      feedback: null,
      createdAt: new Date().toISOString(),
      attempts: 1,
    };
    state.pending.push(entry);
  }

  await saveState(state);

  if (options.notify) {
    try {
      await notifyTelegram("approval-dual", entry, state.chatId);
    } catch (err) {
      console.warn(`[telegram] notification failed: ${(err as Error).message}`);
    }
  }

  return entry;
}

async function main(): Promise<void> {
  console.log(`=== instagram-to-blog daily sync ===`);
  const startedAt = new Date().toISOString();

  try {
    const state = await loadState();
    const token = await ensureToken(state);

    // Skip posts already queued, skipped or published; use the FIXED stop
    // boundary (never lastProcessedId, which advances to the newest post and
    // would block the rest of the backlog).
    const pendingIds = new Set(state.pending.map((e) => e.id));
    const skippedIds = new Set(state.skippedIds);
    const publishedIds = new Set(state.published.map((e) => e.id));
    const stopId = process.env.IG_STOP_POST_ID ?? null;

    const posts = await fetchAllPosts(token);
    const candidates = filterNewPosts(posts, stopId).filter(
      (p) => !pendingIds.has(p.id) && !skippedIds.has(p.id) && !publishedIds.has(p.id),
    );
    console.log(`[instagram] fetched ${posts.length} posts, ${candidates.length} unprocessed`);

    // Track newest post seen (used by watcher to detect truly new posts)
    if (posts.length > 0 && (!state.highestSeenId || BigInt(posts[0].id) > BigInt(state.highestSeenId))) {
      state.highestSeenId = posts[0].id;
    }

    if (candidates.length === 0) {
      if (state.pending.length === 0) state.backlogDone = true;
      await saveState(state);
      console.log(`[sync] nothing to queue (backlog complete: ${!!state.backlogDone})`);
      return;
    }

    // Process oldest-first: the IG backlog publishes in chronological order
    // (candidates come newest-first from the API; reverse before slicing).
    const toProcess = candidates.reverse().slice(0, MAX_POSTS_PER_RUN);
    for (const candidate of toProcess) {
      const entry = await queuePost(candidate, state, { notify: true });
      console.log(`[sync] queued post ${candidate.id} for approval -> ${entry.prepared.mdxPath}`);
    }

    await saveState(state);
    console.log(`[sync] done. ${state.pending.length} post(s) awaiting approval.`);
  } catch (err) {
    console.error("[sync] fatal:", err);
    await sendAlert("[Urban Sync] FATAL error", `${(err as Error).message}\n\nRun: ${startedAt}`);
    process.exitCode = 1;
  }
}

if (import.meta.main) {
  await main();
}
