/**
 * Layer 1 fake pipeline: drives the real orchestration end-to-end in-process
 * with every external mocked — queue (IG) → approve → blog publish → tweet
 * pick (auto-advance to FB) → publish to all social platforms.
 */
import "./helpers/silence";
import { beforeEach, describe, expect, test } from "bun:test";
import type { NewPost } from "../types";
import {
  bufferCalls,
  deployCalls,
  emails,
  gbpCalls,
  getHandler,
  gitCalls,
  h,
  importBot,
  linkedinCalls,
  makeCtx,
  publishOrder,
  resetHarness,
  written,
} from "./helpers/harness";

const bot = await importBot();
const socialHandler = getHandler("^social:(.+)$");
const approveHandler = getHandler("^approve:(.+)$");
const pickTweetHandler = getHandler("^pickTweet:(gemini|deepseek):(.+)$");

const fixturePost: NewPost = {
  id: "200",
  caption: "Carteles pegados en Madrid para el concierto de verano",
  mediaUrl: "https://cdn.test/200.jpg",
  timestamp: "2026-09-10T09:00:00.000Z",
  mediaType: "IMAGE",
};

beforeEach(() => {
  resetHarness();
  delete process.env.FB_ACCESS_TOKEN;
  delete process.env.FB_PAGE_ID;
  // translations succeed (no locale files written in the fake pipeline)
  h.translateImpl = async () => [];
  h.state = {
    pending: [makePendingPost()],
    skippedIds: [],
    published: [],
    tokenExpiresAt: Date.now() + 60 * 24 * 60 * 60 * 1000,
  };
});

function makePendingPost() {
  return {
    id: "200",
    article: {
      title: "Concierto de verano en Madrid",
      description: "Una noche de música en directo.",
      content: "Contenido del artículo.",
      tags: ["música"],
      category: "Conciertos",
    },
    post: fixturePost,
    prepared: makePreparedFixture(),
    status: "pending" as const,
    feedback: null,
    createdAt: "2026-09-10T09:00:00.000Z",
    attempts: 1,
  };
}

function makePreparedFixture() {
  return {
    title: "Concierto de verano en Madrid",
    description: "Una noche de música en directo.",
    content: "Contenido del artículo.",
    tags: ["música"],
    category: "Conciertos",
    slug: "test-post",
    pubDate: "10-09-2026 11:00",
    updatedDate: "10-09-2026 11:00",
    basePath: "/tmp/urbanstyle-test/blog/test-post",
    media_url: fixturePost.mediaUrl,
    igMediaId: fixturePost.id,
    mdxPath: "/tmp/urbanstyle-test/blog/test-post/index.mdx",
    imagePath: "/tmp/urbanstyle-test/blog/test-post/header.jpg",
  };
}

describe("full fake pipeline", () => {
  test("queue → approve → tweet pick → publish everything", async () => {
    // 1) Blog publish via the real approve handler
    await approveHandler(makeCtx(["approve:200", "200"]));
    await new Promise((r) => setTimeout(r, 0));

    expect(written.length).toBe(1);
    expect(deployCalls.buildSite).toBeGreaterThanOrEqual(1);
    expect(gitCalls.some((c) => c.fn === "blog")).toBe(true);
    expect(h.state!.published.length).toBe(1);
    expect(h.state!.published[0]!.social.x?.status).toBe("queued");

    // 2) ▶️ Publicar en redes with nothing prepared → real tweet flow
    await socialHandler(makeCtx(["social:200", "200"]));

    const published = h.state!.published[0]!;
    expect(published.social.x?.status).toBe("queued");
    expect(publishOrder.length).toBe(0);

    // 3) Pick the generated tweet → approves X and auto-advances to Facebook
    const pickCtx = makeCtx(["pickTweet:gemini:200", "gemini", "200"]);
    pickCtx.session.tweetCandidates = {
      "200": { gemini: "Carteles pegados en Madrid https://urbanstylepublicity.com/blog/test-post" },
    };
    await pickTweetHandler(pickCtx);

    expect(published.social.x?.status).toBe("approved");
    expect(published.social.facebook?.status).toBe("approved");

    // 4) ▶️ Publicar en redes → all four platforms publish in order
    await socialHandler(makeCtx(["social:200", "200"]));

    expect(publishOrder).toEqual(["xch", "fbch", "gbp", "linkedin"]);
    expect(bufferCalls.length).toBe(2);
    expect(gbpCalls.length).toBe(1);
    expect(linkedinCalls.length).toBe(1);
    expect(published.social.x?.status).toBe("published");
    expect(published.social.x?.link).toContain("buffer.test");
    expect(published.social.facebook?.status).toBe("published");
    expect(published.social.gbp?.status).toBe("published");
    expect(published.social.linkedin?.status).toBe("published");
  });

  test("a failure on X aborts the run and leaves later platforms untouched", async () => {
    await approveHandler(makeCtx(["approve:200", "200"]));
    await new Promise((r) => setTimeout(r, 0));
    const published = h.state!.published[0]!;
    published.social.x = { status: "approved", tweet: "Texto de X" };
    published.social.facebook = { status: "approved", tweet: "Texto de Facebook" };
    h.createPostImpl = async (channelId) => {
      if (channelId === "xch") throw new Error("Buffer caído");
      return { id: `buf-${channelId}`, externalLink: `https://buffer.test/${channelId}` };
    };

    await socialHandler(makeCtx(["social:200", "200"]));

    expect(publishOrder).toEqual(["xch"]);
    expect(published.social.x?.status).toBe("failed");
    expect(published.social.facebook?.status).toBe("approved");
    expect(gbpCalls.length).toBe(0);
    expect(linkedinCalls.length).toBe(0);
  });

  test("EMAIL BEHAVIOR: exactly one report email for the whole post", async () => {
    await approveHandler(makeCtx(["approve:200", "200"]));
    await new Promise((r) => setTimeout(r, 0));
    const published = h.state!.published[0]!;
    published.social.x = { status: "approved", tweet: "Texto de X" };
    published.social.facebook = { status: "approved", tweet: "Texto de Facebook" };

    await socialHandler(makeCtx(["social:200", "200"]));

    expect(emails.length).toBe(1);
    expect(emails[0]!.subject).toBe("[Urban Sync] Publicado: Concierto de verano en Madrid");
    expect(emails[0]!.body).toContain("Blog: https://urbanstylepublicity.com/blog/test-post");
    expect(emails[0]!.body).toContain("X: ✅ publicado — https://buffer.test/xch");
    expect(emails[0]!.body).toContain("Facebook: ✅ publicado — https://buffer.test/fbch");
    expect(emails[0]!.body).toContain("Google: ✅ publicado — https://gbp.test/post/1");
    expect(emails[0]!.body).toContain("LinkedIn: ✅ publicado — https://linkedin.test/post/1");
  });
});
