/**
 * Email behavior AFTER the consolidation: one report per ▶️ publish attempt.
 * Blog-publish success and per-platform sends no longer email; only blocking
 * failures (blog publish, git sync) and deletions still send immediate alerts.
 */
import "./helpers/silence";
import { beforeEach, describe, expect, test } from "bun:test";
import {
  emails,
  getHandler,
  h,
  importBot,
  makeCtx,
  makePending,
  makePublished,
  resetHarness,
} from "./helpers/harness";

const bot = await importBot();
const socialHandler = getHandler("^social:(.+)$");
const postXHandler = getHandler("^postX:(.+)$");

beforeEach(() => {
  resetHarness();
  delete process.env.FB_ACCESS_TOKEN;
  delete process.env.FB_PAGE_ID;
});

describe("immediate alerts that remain", () => {
  test("blog publish failure sends its own error email", async () => {
    const entry = makePending();
    h.state = { pending: [entry], skippedIds: [], published: [] };
    h.writePostFilesImpl = async () => {
      throw new Error("disk full");
    };

    await bot.publishEntry(entry, h.state, makeCtx(["approve:100", "100"]) as never);

    expect(emails.length).toBe(1);
    expect(emails[0]!.subject).toBe("[Urban Sync] Error al publicar");
    expect(emails[0]!.body).toContain("disk full");
  });

  test("blog publish success sends NO email (deferred to the social report)", async () => {
    const entry = makePending();
    h.state = { pending: [entry], skippedIds: [], published: [] };

    await bot.publishEntry(entry, h.state, makeCtx(["approve:100", "100"]) as never);
    await new Promise((r) => setTimeout(r, 0));

    expect(emails.length).toBe(0);
  });

  test("git sync failure still sends an immediate email", async () => {
    const entry = makePending();
    h.state = { pending: [entry], skippedIds: [], published: [] };
    h.commitAndPushImpl = () => {
      throw new Error("git boom");
    };

    await bot.publishEntry(entry, h.state, makeCtx(["approve:100", "100"]) as never);
    await new Promise((r) => setTimeout(r, 0));

    expect(emails.length).toBe(1);
    expect(emails[0]!.subject).toBe("[Urban Sync] git sync failed");
  });

  test("deleting a post still sends its own email", async () => {
    h.state = { pending: [], skippedIds: [], published: [makePublished({})] };

    await bot.removePublishedPost("100", makeCtx(["remove:100", "100"]) as never);

    expect(emails.length).toBe(1);
    expect(emails[0]!.subject).toBe("[Urban Sync] Eliminado: Test Post");
  });
});

describe("per-platform helpers no longer email", () => {
  test("publishing each platform directly sends no email", async () => {
    const published = makePublished({
      x: { status: "approved", tweet: "Hola mundo" },
      facebook: { status: "approved", tweet: "Hola mundo" },
    });
    h.state = { pending: [], skippedIds: [], published: [published] };

    await bot.publishToX(published, h.state);
    await bot.publishToFb(published, h.state);
    await bot.publishToGbp(published, h.state);
    await bot.publishToLinkedIn(published, h.state);

    expect(emails.length).toBe(0);
  });

  test("a platform failure alone sends no email", async () => {
    const published = makePublished({ x: { status: "approved", tweet: "Hola" } });
    h.state = { pending: [], skippedIds: [], published: [published] };
    h.createPostImpl = async () => {
      throw new Error("X boom");
    };

    await bot.publishToX(published, h.state);

    expect(emails.length).toBe(0);
  });
});

describe("one report email per publish attempt", () => {
  test("full success report lists blog URL and every platform link", async () => {
    const published = makePublished({
      x: { status: "approved", tweet: "Hola mundo" },
      facebook: { status: "approved", tweet: "Hola mundo" },
    });
    h.state = { pending: [], skippedIds: [], published: [published] };

    await socialHandler(makeCtx(["social:100", "100"]));

    expect(emails.length).toBe(1);
    const report = emails[0]!;
    expect(report.subject).toBe("[Urban Sync] Publicado: Test Post");
    expect(report.body).toContain("Blog: https://urbanstylepublicity.com/blog/test-post");
    expect(report.body).toContain("X: ✅ publicado — https://buffer.test/xch");
    expect(report.body).toContain("Facebook: ✅ publicado — https://buffer.test/fbch");
    expect(report.body).toContain("Google: ✅ publicado — https://gbp.test/post/1");
    expect(report.body).toContain("LinkedIn: ✅ publicado — https://linkedin.test/post/1");
  });

  test("partial failure report marks the error and the platforms not attempted", async () => {
    const published = makePublished({
      x: { status: "approved", tweet: "Hola mundo" },
      facebook: { status: "approved", tweet: "Hola mundo" },
    });
    h.state = { pending: [], skippedIds: [], published: [published] };
    h.createPostImpl = async (channelId) => {
      if (channelId === "xch") throw new Error("X boom");
      return { id: `buf-${channelId}`, externalLink: `https://buffer.test/${channelId}` };
    };

    await socialHandler(makeCtx(["social:100", "100"]));

    expect(emails.length).toBe(1);
    const report = emails[0]!;
    expect(report.subject).toBe("[Urban Sync] Publicado con fallos: Test Post");
    expect(report.body).toContain("X: ❌ error — X boom");
    expect(report.body).toContain("Facebook: ⏭ no intentado");
    expect(report.body).toContain("Google: ⏭ no intentado");
    expect(report.body).toContain("LinkedIn: ⏭ no intentado");
  });

  test("a retry reports previously published platforms with their stored links", async () => {
    const published = makePublished({
      x: { status: "published", tweet: "Hola mundo", link: "https://x.com/pegadacarteles/status/1" },
      facebook: { status: "approved", tweet: "Hola mundo" },
    });
    h.state = { pending: [], skippedIds: [], published: [published] };

    await socialHandler(makeCtx(["social:100", "100"]));

    expect(emails.length).toBe(1);
    const report = emails[0]!;
    expect(report.body).toContain("X: ✅ ya publicado — https://x.com/pegadacarteles/status/1");
    expect(report.body).toContain("Facebook: ✅ publicado — https://buffer.test/fbch");
    expect(report.body).toContain("Google: ✅ publicado");
    expect(report.body).toContain("LinkedIn: ✅ publicado");
  });

  test("the legacy single-platform wrapper also sends exactly one report", async () => {
    const published = makePublished({ x: { status: "approved", tweet: "Hola" } });
    h.state = { pending: [], skippedIds: [], published: [published] };

    await postXHandler(makeCtx(["postX:100", "100"]));

    expect(emails.length).toBe(1);
    expect(emails[0]!.subject).toBe("[Urban Sync] Publicado: Test Post");
    expect(emails[0]!.body).toContain("X: ✅ publicado — https://buffer.test/xch");
    expect(emails[0]!.body).toContain("Facebook: ⏭ no intentado");
  });

  test("publish + social run sends ONE email for the whole post", async () => {
    const entry = makePending();
    h.state = { pending: [entry], skippedIds: [], published: [] };
    await bot.publishEntry(entry, h.state, makeCtx(["approve:100", "100"]) as never);
    await new Promise((r) => setTimeout(r, 0));
    expect(emails.length).toBe(0);

    const published = h.state!.published[0]!;
    published.social.x = { status: "approved", tweet: "Hola mundo" };
    published.social.facebook = { status: "approved", tweet: "Hola mundo" };
    await socialHandler(makeCtx(["social:100", "100"]));

    expect(emails.length).toBe(1);
    expect(emails[0]!.subject).toBe("[Urban Sync] Publicado: Test Post");
    expect(emails[0]!.body).toContain("Blog: https://urbanstylepublicity.com/blog/test-post");
    expect(emails[0]!.body).toContain("X: ✅ publicado — https://buffer.test/xch");
    expect(emails[0]!.body).toContain("Facebook: ✅ publicado — https://buffer.test/fbch");
    expect(emails[0]!.body).toContain("Google: ✅ publicado — https://gbp.test/post/1");
    expect(emails[0]!.body).toContain("LinkedIn: ✅ publicado — https://linkedin.test/post/1");
  });
});
