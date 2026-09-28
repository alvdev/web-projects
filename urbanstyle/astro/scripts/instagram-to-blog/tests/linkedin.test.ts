/**
 * LinkedIn link resolution: Buffer publishes asynchronously, so the
 * createPost response carries no externalLink. These tests lock the polling
 * behavior that recovers the published post URL (getLinkedInPost /
 * waitForLinkedInExternalLink) with the Buffer HTTP layer stubbed.
 */
import "./helpers/silence";
import { afterEach, beforeEach, describe, expect, test } from "bun:test";
import { getLinkedInPost, waitForLinkedInExternalLink } from "../social/linkedin";

interface PostResponse {
  id: string;
  status: string;
  externalLink?: string | null;
  error?: { message: string } | null;
}

const realFetch = globalThis.fetch;
const savedToken = process.env.GBP_BUFFER_ACCESS_TOKEN;

let responses: PostResponse[] = [];
let callIndex = 0;

function installFetch(): void {
  globalThis.fetch = (async (_input: RequestInfo | URL, init?: RequestInit) => {
    const next = responses[Math.min(callIndex, responses.length - 1)];
    callIndex += 1;
    return new Response(JSON.stringify({ data: { post: next } }), {
      status: 200,
      headers: { "content-type": "application/json" },
    });
  }) as typeof fetch;
}

beforeEach(() => {
  responses = [];
  callIndex = 0;
  process.env.GBP_BUFFER_ACCESS_TOKEN = "test-token";
  installFetch();
});

afterEach(() => {
  globalThis.fetch = realFetch;
  if (savedToken === undefined) delete process.env.GBP_BUFFER_ACCESS_TOKEN;
  else process.env.GBP_BUFFER_ACCESS_TOKEN = savedToken;
});

describe("getLinkedInPost", () => {
  test("returns the post status and external link from Buffer", async () => {
    responses = [{ id: "li-1", status: "sent", externalLink: "https://linkedin.test/post/1" }];

    const post = await getLinkedInPost("li-1");

    expect(post.status).toBe("sent");
    expect(post.externalLink).toBe("https://linkedin.test/post/1");
    expect(callIndex).toBe(1);
  });
});

describe("waitForLinkedInExternalLink", () => {
  test("polls until Buffer exposes the link", async () => {
    responses = [
      { id: "li-1", status: "sending", externalLink: null },
      { id: "li-1", status: "sent", externalLink: "https://linkedin.test/post/1" },
    ];

    const link = await waitForLinkedInExternalLink("li-1", { timeoutMs: 500, intervalMs: 5 });

    expect(link).toBe("https://linkedin.test/post/1");
    expect(callIndex).toBe(2);
  });

  test("gives up after the timeout without a link", async () => {
    responses = [{ id: "li-1", status: "sending", externalLink: null }];

    const link = await waitForLinkedInExternalLink("li-1", { timeoutMs: 20, intervalMs: 5 });

    expect(link).toBeUndefined();
  });

  test("surfaces the Buffer publishing error", async () => {
    responses = [
      {
        id: "li-1",
        status: "error",
        externalLink: null,
        error: { message: "LinkedIn rejected the post" },
      },
    ];

    await expect(waitForLinkedInExternalLink("li-1", { timeoutMs: 500, intervalMs: 5 })).rejects.toThrow(
      "LinkedIn rejected the post",
    );
  });
});
