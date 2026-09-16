/**
 * Behavior lock for the blog publish + social publish pipeline. These tests
 * MUST NOT assert on emails — email behavior is characterized separately in
 * publish.emails.test.ts so the email consolidation refactor only changes that
 * file.
 */
import "./helpers/silence";
import { beforeEach, describe, expect, test } from "bun:test";
import {
  bufferCalls,
  deployCalls,
  fbCalls,
  getHandler,
  gbpCalls,
  gitCalls,
  h,
  importBot,
  linkedinCalls,
  makeCtx,
  makePublished,
  makePending,
  publishOrder,
  resetHarness,
  written,
  xverifyCalls,
} from "./helpers/harness";

const bot = await importBot();
const socialHandler = getHandler("^social:(.+)$");
const postXHandler = getHandler("^postX:(.+)$");
const postFbHandler = getHandler("^postFb:(.+)$");

type BotCtx = Parameters<typeof bot.publishEntry>[2];
const asCtx = (ctx: ReturnType<typeof makeCtx>): BotCtx => ctx as unknown as BotCtx;

function emptyState() {
  h.state = { pending: [], skippedIds: [], published: [] };
  return h.state;
}

function stateWith(published: ReturnType<typeof makePublished>) {
  h.state = { pending: [], skippedIds: [], published: [published] };
  return h.state!;
}

beforeEach(() => {
  resetHarness();
  delete process.env.FB_ACCESS_TOKEN;
  delete process.env.FB_PAGE_ID;
});

describe("publishToX", () => {
  test("publishes the approved tweet and persists published state", async () => {
    const published = makePublished({ x: { status: "approved", tweet: "Hola mundo" } });
    const state = stateWith(published);

    const res = await bot.publishToX(published, state);

    expect(res.ok).toBe(true);
    expect(res.line).toContain("https://buffer.test/xch");
    expect(published.social.x?.status).toBe("published");
    expect(published.social.x?.publishedAt).toBeTruthy();
    expect(published.social.x?.error).toBeUndefined();
    expect(bufferCalls.length).toBe(1);
    expect(bufferCalls[0]?.channelId).toBe("xch");
  });

  test("short-circuits when already published", async () => {
    const published = makePublished({ x: { status: "published", tweet: "Hola" } });
    const res = await bot.publishToX(published, stateWith(published));
    expect(res.ok).toBe(true);
    expect(res.line).toContain("ya estaba publicado");
    expect(bufferCalls.length).toBe(0);
  });

  test("refuses when there is no approved tweet", async () => {
    const published = makePublished({ x: { status: "queued" } });
    const res = await bot.publishToX(published, stateWith(published));
    expect(res.ok).toBe(false);
    expect(res.line).toContain("no hay tweet aprobado");
    expect(bufferCalls.length).toBe(0);
  });

  test("refuses when mentions are unverified and leaves state untouched", async () => {
    const published = makePublished({ x: { status: "approved", tweet: "Hola @artista" } });
    const state = stateWith(published);
    h.cachedHandlesCoverImpl = () => false;
    h.verifyTweetHandlesImpl = async () => [
      { handle: "artista", status: "unverified", verifiedAt: new Date().toISOString() },
    ];

    const res = await bot.publishToX(published, state);

    expect(res.ok).toBe(false);
    expect(res.line).toContain("menciones sin verificar");
    expect(published.social.x?.status).toBe("approved");
    expect(bufferCalls.length).toBe(0);
    expect(state.published[0]?.social.x?.status).toBe("approved");
  });

  test("records the failure when Buffer errors", async () => {
    const published = makePublished({ x: { status: "approved", tweet: "Hola" } });
    stateWith(published);
    h.createPostImpl = async () => {
      throw new Error("X boom");
    };

    const res = await bot.publishToX(published, h.state!);

    expect(res.ok).toBe(false);
    expect(res.line).toContain("X boom");
    expect(published.social.x?.status).toBe("failed");
    expect(published.social.x?.error).toBe("X boom");
  });
});

describe("publishToFb", () => {
  test("uses the direct Graph API when configured", async () => {
    process.env.FB_ACCESS_TOKEN = "token";
    process.env.FB_PAGE_ID = "page";
    const published = makePublished({
      x: { status: "published", tweet: "Hola" },
      facebook: { status: "approved", tweet: "Hola mundo" },
    });
    stateWith(published);
    h.createFbPostImpl = async () => ({ id: "p1", externalLink: "https://facebook.test/1_2" });

    const res = await bot.publishToFb(published, h.state!);

    expect(res.ok).toBe(true);
    expect(res.line).toContain("Facebook (directo)");
    expect(fbCalls.length).toBe(1);
    expect(bufferCalls.length).toBe(0);
    expect(published.social.facebook?.status).toBe("published");
  });

  test("falls back to Buffer when the Graph API fails", async () => {
    process.env.FB_ACCESS_TOKEN = "token";
    process.env.FB_PAGE_ID = "page";
    const published = makePublished({ facebook: { status: "approved", tweet: "Hola mundo" } });
    stateWith(published);

    const res = await bot.publishToFb(published, h.state!);

    expect(res.ok).toBe(true);
    expect(res.line).toContain("Facebook:");
    expect(fbCalls.length).toBe(1);
    expect(bufferCalls.length).toBe(1);
    expect(bufferCalls[0]?.channelId).toBe("fbch");
    expect(published.social.facebook?.status).toBe("published");
  });

  test("refuses when there is no approved Facebook text", async () => {
    const published = makePublished({ facebook: { status: "queued" } });
    const res = await bot.publishToFb(published, stateWith(published));
    expect(res.ok).toBe(false);
    expect(res.line).toContain("no hay post aprobado");
  });

  test("refuses unverified mentions unless explicitly approved", async () => {
    const published = makePublished({ facebook: { status: "approved", tweet: "Hola @artista" } });
    const state = stateWith(published);

    const res = await bot.publishToFb(published, state);

    expect(res.ok).toBe(false);
    expect(res.line).toContain("menciones sin verificar");
    expect(bufferCalls.length).toBe(0);
  });

  test("publishes mentions when the user approved them explicitly", async () => {
    const published = makePublished({
      facebook: { status: "approved", tweet: "Hola @artista", handlesApproved: true },
    });
    const res = await bot.publishToFb(published, stateWith(published));
    expect(res.ok).toBe(true);
    expect(bufferCalls.length).toBe(1);
  });

  test("records the failure when Buffer errors", async () => {
    const published = makePublished({ facebook: { status: "approved", tweet: "Hola mundo" } });
    stateWith(published);
    h.createPostImpl = async () => {
      throw new Error("FB boom");
    };

    const res = await bot.publishToFb(published, h.state!);

    expect(res.ok).toBe(false);
    expect(res.line).toContain("FB boom");
    expect(published.social.facebook?.status).toBe("failed");
    expect(published.social.facebook?.error).toBe("FB boom");
  });
});

describe("publishToGbp", () => {
  test("publishes the Facebook text with mentions stripped", async () => {
    const published = makePublished({
      facebook: { status: "approved", tweet: "Hola @artista Madrid" },
    });
    const res = await bot.publishToGbp(published, stateWith(published));

    expect(res.ok).toBe(true);
    expect(gbpCalls.length).toBe(1);
    expect(gbpCalls[0]?.text).toBe("Hola Madrid");
    expect(published.social.gbp?.status).toBe("published");
  });

  test("refuses without an approved Facebook text", async () => {
    const published = makePublished({});
    const res = await bot.publishToGbp(published, stateWith(published));
    expect(res.ok).toBe(false);
    expect(res.line).toContain("no hay texto de Facebook aprobado");
  });

  test("records the failure when Buffer errors", async () => {
    const published = makePublished({ facebook: { status: "approved", tweet: "Hola Madrid" } });
    stateWith(published);
    h.createGbpPostImpl = async () => {
      throw new Error("GBP boom");
    };
    const res = await bot.publishToGbp(published, h.state!);
    expect(res.ok).toBe(false);
    expect(published.social.gbp?.status).toBe("failed");
    expect(published.social.gbp?.error).toBe("GBP boom");
  });
});

describe("publishToLinkedIn", () => {
  test("publishes the Facebook text with mentions stripped", async () => {
    const published = makePublished({
      facebook: { status: "approved", tweet: "Hola @artista Madrid" },
    });
    const res = await bot.publishToLinkedIn(published, stateWith(published));

    expect(res.ok).toBe(true);
    expect(linkedinCalls.length).toBe(1);
    expect(linkedinCalls[0]?.text).toBe("Hola Madrid");
    expect(published.social.linkedin?.status).toBe("published");
  });

  test("refuses without an approved Facebook text", async () => {
    const published = makePublished({});
    const res = await bot.publishToLinkedIn(published, stateWith(published));
    expect(res.ok).toBe(false);
    expect(res.line).toContain("no hay texto de Facebook aprobado");
  });

  test("records the failure when Buffer errors", async () => {
    const published = makePublished({ facebook: { status: "approved", tweet: "Hola Madrid" } });
    stateWith(published);
    h.createLinkedInPostImpl = async () => {
      throw new Error("LI boom");
    };
    const res = await bot.publishToLinkedIn(published, h.state!);
    expect(res.ok).toBe(false);
    expect(published.social.linkedin?.status).toBe("failed");
    expect(published.social.linkedin?.error).toBe("LI boom");
  });
});

describe("publishEntry (blog pipeline)", () => {
  test("moves the post from pending to published and queues socials", async () => {
    const entry = makePending();
    h.state = { pending: [entry], skippedIds: [], published: [] };
    const ctx = makeCtx(["approve:100", "100"]);

    await bot.publishEntry(entry, h.state, asCtx(ctx));

    expect(written.length).toBe(1);
    expect(deployCalls.buildSite).toBeGreaterThanOrEqual(1);
    expect(deployCalls.uploadDist).toBeGreaterThanOrEqual(1);
    expect(gitCalls.some((c) => c.fn === "blog" && c.slug === "test-post")).toBe(true);
    expect(h.state!.pending.length).toBe(0);
    expect(h.state!.published.length).toBe(1);
    expect(h.state!.published[0]!.id).toBe("100");
    expect(h.state!.published[0]!.social.x?.status).toBe("queued");
    expect(h.state!.lastProcessedId).toBe("100");
    expect(ctx.edits.some((e) => e.text.includes("✅ *Publicado:*"))).toBe(true);
    await new Promise((r) => setTimeout(r, 0));
  });

  test("keeps the post pending when writing the MDX fails", async () => {
    const entry = makePending();
    h.state = { pending: [entry], skippedIds: [], published: [] };
    h.writePostFilesImpl = async () => {
      throw new Error("disk full");
    };
    const ctx = makeCtx(["approve:100", "100"]);

    await bot.publishEntry(entry, h.state, asCtx(ctx));

    expect(h.state!.pending.length).toBe(1);
    expect(h.state!.published.length).toBe(0);
    expect(ctx.edits.some((e) => e.text.includes("❌ Error al publicar: disk full"))).toBe(true);
  });

  test("a git sync failure never fails the publish", async () => {
    const entry = makePending();
    h.state = { pending: [entry], skippedIds: [], published: [] };
    h.commitAndPushImpl = () => {
      throw new Error("git boom");
    };
    const ctx = makeCtx(["approve:100", "100"]);

    await bot.publishEntry(entry, h.state, asCtx(ctx));

    expect(h.state!.published.length).toBe(1);
    expect(h.state!.pending.length).toBe(0);
    await new Promise((r) => setTimeout(r, 0));
  });
});

describe("social: callback orchestration", () => {
  test("publishes X → Facebook → Google → LinkedIn and reports the final status", async () => {
    const published = makePublished({
      x: { status: "approved", tweet: "Hola mundo" },
      facebook: { status: "approved", tweet: "Hola mundo" },
    });
    stateWith(published);
    const ctx = makeCtx(["social:100", "100"]);

    await socialHandler(ctx);

    expect(publishOrder).toEqual(["xch", "fbch", "gbp", "linkedin"]);
    expect(published.social.x?.status).toBe("published");
    expect(published.social.facebook?.status).toBe("published");
    expect(published.social.gbp?.status).toBe("published");
    expect(published.social.linkedin?.status).toBe("published");
    expect(ctx.edits.some((e) => e.text.includes("✅ *Publicado en redes:*"))).toBe(true);
  });

  test("stops at the first failure and does not attempt later platforms", async () => {
    const published = makePublished({
      x: { status: "approved", tweet: "Hola mundo" },
      facebook: { status: "approved", tweet: "Hola mundo" },
    });
    stateWith(published);
    h.createPostImpl = async (channelId) => {
      if (channelId === "xch") throw new Error("X boom");
      return { id: `buf-${channelId}`, externalLink: `https://buffer.test/${channelId}` };
    };
    const ctx = makeCtx(["social:100", "100"]);

    await socialHandler(ctx);

    expect(publishOrder).toEqual(["xch"]);
    expect(fbCalls.length).toBe(0);
    expect(gbpCalls.length).toBe(0);
    expect(linkedinCalls.length).toBe(0);
    expect(ctx.edits.some((e) => e.text.includes("❌ *X:* X boom"))).toBe(true);
  });

  test("retries only the platforms still pending", async () => {
    const published = makePublished({
      x: { status: "published", tweet: "Hola mundo" },
      facebook: { status: "approved", tweet: "Hola mundo" },
    });
    stateWith(published);
    const ctx = makeCtx(["social:100", "100"]);

    await socialHandler(ctx);

    expect(bufferCalls.some((c) => c.channelId === "xch")).toBe(false);
    expect(bufferCalls.some((c) => c.channelId === "fbch")).toBe(true);
    expect(gbpCalls.length).toBe(1);
    expect(linkedinCalls.length).toBe(1);
  });

  test("reports when every platform is already published", async () => {
    const published = makePublished({
      x: { status: "published", tweet: "a" },
      facebook: { status: "published", tweet: "b" },
      gbp: { status: "published" },
      linkedin: { status: "published" },
    });
    stateWith(published);
    const ctx = makeCtx(["social:100", "100"]);

    await socialHandler(ctx);

    expect(publishOrder.length).toBe(0);
    expect(ctx.edits.some((e) => e.text.includes("ya está publicado en X, Facebook, Google y LinkedIn"))).toBe(true);
  });

  test("prepares the tweet when no approved text exists yet", async () => {
    const published = makePublished({ x: { status: "queued" } });
    stateWith(published);
    const ctx = makeCtx(["social:100", "100"]);

    await socialHandler(ctx);

    expect(publishOrder.length).toBe(0);
    expect(ctx.replies.some((r) => r.text.includes("Selecciona la versión del tweet"))).toBe(true);
  });

  test("ignores unknown post ids", async () => {
    emptyState();
    const ctx = makeCtx(["social:999", "999"]);

    await socialHandler(ctx);

    expect(ctx.answers).toContain("Post no encontrado en publicados");
    expect(publishOrder.length).toBe(0);
  });
});

describe("legacy postX/postFb wrappers", () => {
  test("postX publishes a single platform and reports it", async () => {
    const published = makePublished({ x: { status: "approved", tweet: "Hola" } });
    stateWith(published);
    const ctx = makeCtx(["postX:100", "100"]);

    await postXHandler(ctx);

    expect(bufferCalls.length).toBe(1);
    expect(ctx.edits.some((e) => e.text.includes("https://buffer.test/xch"))).toBe(true);
  });

  test("postFb publishes a single platform and reports it", async () => {
    const published = makePublished({ facebook: { status: "approved", tweet: "Hola mundo" } });
    stateWith(published);
    const ctx = makeCtx(["postFb:100", "100"]);

    await postFbHandler(ctx);

    expect(bufferCalls.length).toBe(1);
    expect(ctx.edits.some((e) => e.text.includes("Facebook"))).toBe(true);
  });
});

describe("removePublishedPost", () => {
  test("removes the post from the site and the published list", async () => {
    const published = makePublished({});
    stateWith(published);
    const ctx = makeCtx(["remove:100", "100"]);

    await bot.removePublishedPost("100", asCtx(ctx));

    expect(deployCalls.removeRemoteDir).toEqual(["/blog/test-post"]);
    expect(h.state!.published.length).toBe(0);
    expect(h.state!.skippedIds).toContain("100");
    expect(ctx.edits.some((e) => e.text.includes("🗑 *Eliminado:*"))).toBe(true);
  });

  test("keeps the post when the deploy fails", async () => {
    const published = makePublished({});
    stateWith(published);
    h.uploadDistImpl = async () => {
      throw new Error("ftp down");
    };
    const ctx = makeCtx(["remove:100", "100"]);

    await bot.removePublishedPost("100", asCtx(ctx));

    expect(h.state!.published.length).toBe(1);
    expect(ctx.edits.some((e) => e.text.includes("❌ Error al eliminar: ftp down"))).toBe(true);
  });
});

test("xverify is not consulted for posts with no handles", async () => {
  const published = makePublished({ x: { status: "approved", tweet: "Hola" } });
  stateWith(published);
  await bot.publishToX(published, h.state!);
  expect(xverifyCalls.verifyTweetHandles).toBe(0);
});
