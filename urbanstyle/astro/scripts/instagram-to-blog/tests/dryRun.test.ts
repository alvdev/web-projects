/**
 * Locks the DRY_RUN sandbox hooks: default-off (real code rejects without
 * credentials) and, when DRY_RUN=1, no network is attempted and fake links
 * are returned.
 */
import "./helpers/silence";
import { afterAll, beforeEach, describe, expect, test } from "bun:test";
import { removeRemoteDir, uploadDist } from "../deploy";
import { createPost, getFbChannel, getXChannel } from "../social/buffer";
import { createFbPost, deleteFbPost } from "../social/facebook";
import { createGbpPost, getGbpChannel } from "../social/gbp";
import { createLinkedInPost, getLinkedInChannel } from "../social/linkedin";

const DRY_KEYS = [
  "DRY_RUN",
  "FTP_HOST",
  "FTP_USER",
  "FTP_PASSWORD",
  "FTP_REMOTE_PATH",
  "BUFFER_ACCESS_TOKEN",
  "GBP_BUFFER_ACCESS_TOKEN",
  "FB_ACCESS_TOKEN",
  "FB_PAGE_ID",
] as const;
const saved: Record<string, string | undefined> = {};
for (const key of DRY_KEYS) saved[key] = process.env[key];

beforeEach(() => {
  for (const key of DRY_KEYS) delete process.env[key];
});

afterAll(() => {
  for (const key of DRY_KEYS) {
    if (saved[key] === undefined) delete process.env[key];
    else process.env[key] = saved[key];
  }
});

describe("hooks disabled by default", () => {
  test("uploadDist requires FTP credentials", async () => {
    await expect(uploadDist()).rejects.toThrow("FTP env vars missing");
  });

  test("removeRemoteDir requires FTP credentials", async () => {
    await expect(removeRemoteDir("/blog/x")).rejects.toThrow("FTP env vars missing");
  });

  test("Buffer createPost requires a token", async () => {
    await expect(createPost("ch", "hola")).rejects.toThrow("BUFFER_ACCESS_TOKEN not set");
  });

  test("GBP createGbpPost requires a token", async () => {
    await expect(createGbpPost("hola")).rejects.toThrow("GBP_BUFFER_ACCESS_TOKEN not set");
  });

  test("LinkedIn createLinkedInPost requires a token", async () => {
    await expect(createLinkedInPost("hola")).rejects.toThrow("GBP_BUFFER_ACCESS_TOKEN not set");
  });
});

describe("DRY_RUN=1", () => {
  beforeEach(() => {
    process.env.DRY_RUN = "1";
  });

  test("FTPS upload and remote removal are skipped", async () => {
    await expect(uploadDist()).resolves.toEqual({ uploaded: 0, skipped: 0, total: 0, pending: 0 });
    await expect(removeRemoteDir("/blog/x")).resolves.toBeUndefined();
  });

  test("Buffer channels and posts return fake data", async () => {
    expect(await getXChannel()).toEqual({ id: "xch", name: "dry-run twitter" });
    expect(await getFbChannel()).toEqual({ id: "fbch", name: "dry-run facebook" });
    const post = await createPost("xch", "hola");
    expect(post.id).toBe("dry-xch");
    expect(post.externalLink).toContain("dry-run.invalid");
  });

  test("Facebook Graph returns a fake link and delete is a no-op", async () => {
    const post = await createFbPost("hola", "https://example.test");
    expect(post.externalLink).toContain("dry-run.invalid");
    await expect(deleteFbPost("post-1")).resolves.toBeUndefined();
  });

  test("GBP and LinkedIn return fake data", async () => {
    expect(await getGbpChannel()).toEqual({ id: "gbpch", name: "dry-run gbp" });
    expect(await getLinkedInChannel()).toEqual({ id: "lich", name: "dry-run linkedin" });
    expect((await createGbpPost("hola")).externalLink).toContain("dry-run.invalid");
    expect((await createLinkedInPost("hola")).externalLink).toContain("dry-run.invalid");
  });
});
