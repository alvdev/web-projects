/**
 * Daily sync (index.ts) and hourly watcher (watcher.ts) behavior. Instagram
 * HTTP is stubbed at the global fetch level so the real fetchAllPosts /
 * filterNewPosts / cleanCaption code paths run.
 */
import "./helpers/silence";
import { afterEach, beforeEach, describe, expect, test } from "bun:test";
import { join } from "node:path";
import type { IgPost } from "../types";
import {
  emails,
  h,
  makePending,
  resetHarness,
  SCRIPT_ROOT,
  telegramCalls,
} from "./helpers/harness";

const { main: syncMain, queuePost } = await import(join(SCRIPT_ROOT, "index.ts"));
const { main: watcherMain } = await import(join(SCRIPT_ROOT, "watcher.ts"));

const realFetch = globalThis.fetch;
let igPosts: IgPost[] = [];
let mediaStatus = 200;
const realExitCode = process.exitCode;

function installFetch(): void {
  globalThis.fetch = (async (input: RequestInfo | URL) => {
    const url = String(input);
    if (url.includes("/me/media")) {
      if (mediaStatus !== 200) return new Response("boom", { status: mediaStatus });
      return new Response(JSON.stringify({ data: igPosts, paging: { next: null } }), {
        status: 200,
        headers: { "content-type": "application/json" },
      });
    }
    return new Response(JSON.stringify({ id: "1", username: "test" }), {
      status: 200,
      headers: { "content-type": "application/json" },
    });
  }) as typeof fetch;
}

function igPost(id: string, timestamp: string): IgPost {
  return {
    id,
    caption: `caption ${id}`,
    media_type: "IMAGE",
    media_url: `https://cdn.test/${id}.jpg`,
    timestamp,
  };
}

const newPost = {
  id: "200",
  caption: "caption",
  mediaUrl: "https://cdn.test/200.jpg",
  timestamp: "2026-09-01T09:00:00.000Z",
  mediaType: "IMAGE",
};

beforeEach(() => {
  resetHarness();
  installFetch();
  igPosts = [];
  mediaStatus = 200;
  process.env.IG_ACCESS_TOKEN = "test-token";
  h.state = { pending: [], skippedIds: [], published: [], tokenExpiresAt: Date.now() + 60 * 24 * 60 * 60 * 1000 };
});

afterEach(() => {
  globalThis.fetch = realFetch;
  process.exitCode = realExitCode;
});

describe("queuePost", () => {
  test("creates a pending entry and notifies Telegram", async () => {
    const entry = await queuePost(newPost, h.state!, { notify: true });

    expect(entry.id).toBe("200");
    expect(entry.status).toBe("pending");
    expect(entry.attempts).toBe(1);
    expect(h.state!.pending.length).toBe(1);
    expect(telegramCalls.some((c) => c.kind === "approval-dual")).toBe(true);
  });

  test("regenerates an existing entry instead of duplicating it", async () => {
    const first = await queuePost(newPost, h.state!);
    first.feedback = "old feedback";

    const second = await queuePost(newPost, h.state!, { entryId: "200" });

    expect(h.state!.pending.length).toBe(1);
    expect(second.attempts).toBe(2);
    expect(second.feedback).toBeNull();
    expect(second.status).toBe("pending");
  });
});

describe("index main (daily sync)", () => {
  test("marks the backlog done when there is nothing to queue", async () => {
    h.state!.backlogDone = false;

    await syncMain();

    expect(h.state!.backlogDone).toBe(true);
    expect(h.state!.pending.length).toBe(0);
  });

  test("queues the oldest unprocessed candidate first", async () => {
    igPosts = [igPost("300", "2026-09-10T09:00:00.000Z"), igPost("250", "2026-09-05T09:00:00.000Z")];

    await syncMain();

    expect(h.state!.pending.length).toBe(1);
    expect(h.state!.pending[0]!.id).toBe("250");
    expect(h.state!.highestSeenId).toBe("300");
  });

  test("skips posts already pending, skipped or published", async () => {
    h.state!.pending = [makePending({ id: "250" })];
    h.state!.skippedIds = ["300"];
    igPosts = [igPost("300", "2026-09-10T09:00:00.000Z"), igPost("250", "2026-09-05T09:00:00.000Z")];

    await syncMain();

    expect(h.state!.pending.length).toBe(1);
  });
});

describe("watcher main (hourly)", () => {
  test("skips while the backlog is still running", async () => {
    h.state!.backlogDone = false;
    igPosts = [igPost("300", "2026-09-10T09:00:00.000Z")];

    await watcherMain();

    expect(h.state!.pending.length).toBe(0);
  });

  test("skips while posts await approval", async () => {
    h.state!.backlogDone = true;
    h.state!.pending = [makePending({ id: "999" })];
    igPosts = [igPost("300", "2026-09-10T09:00:00.000Z")];

    await watcherMain();

    expect(h.state!.pending.length).toBe(1);
  });

  test("initializes highestSeenId silently on first run", async () => {
    h.state!.backlogDone = true;
    igPosts = [igPost("300", "2026-09-10T09:00:00.000Z")];

    await watcherMain();

    expect(h.state!.highestSeenId).toBe("300");
    expect(h.state!.pending.length).toBe(0);
  });

  test("queues truly new posts only", async () => {
    h.state!.backlogDone = true;
    h.state!.highestSeenId = "250";
    igPosts = [igPost("300", "2026-09-10T09:00:00.000Z"), igPost("250", "2026-09-05T09:00:00.000Z")];

    await watcherMain();

    expect(h.state!.pending.length).toBe(1);
    expect(h.state!.pending[0]!.id).toBe("300");
    expect(h.state!.highestSeenId).toBe("300");
  });

  test("alerts by email on a fatal error", async () => {
    h.state!.backlogDone = true;
    mediaStatus = 500;

    await watcherMain();

    expect(emails.some((e) => e.subject === "[Urban Sync] Watcher FATAL error")).toBe(true);
  });
});
