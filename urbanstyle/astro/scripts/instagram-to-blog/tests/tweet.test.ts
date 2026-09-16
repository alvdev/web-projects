import "./helpers/silence";
import { afterEach, beforeEach, describe, expect, mock, test } from "bun:test";
import { setSystemTime } from "bun:test";
import type { PreparedPost } from "../types";

let generateTextCompletionImpl: (prompt: string, provider: string, opts?: unknown) => Promise<string> =
  async () => "texto base";

mock.module("../llm", () => ({
  generateTextCompletion: (prompt: string, provider: string, opts?: unknown) =>
    generateTextCompletionImpl(prompt, provider, opts),
}));

const { buildTweetPrompt, formatTweetPreview, generateTweets, shouldUsePastTense, tweetUrlFor } =
  await import("../tweet");

const post: PreparedPost = {
  title: "Concierto en Madrid",
  description: "Descripción",
  content: "Contenido",
  tags: ["tag"],
  category: "Categoría",
  slug: "concierto-en-madrid",
  pubDate: "01-09-2026 10:00",
  updatedDate: "01-09-2026 10:00",
  basePath: "/tmp/blog/concierto-en-madrid",
  media_url: "https://example.test/img.jpg",
  igMediaId: "100",
  mdxPath: "/tmp/blog/concierto-en-madrid/index.mdx",
  imagePath: "/tmp/blog/concierto-en-madrid/header.jpg",
};

beforeEach(() => {
  setSystemTime(new Date("2026-09-16T12:00:00.000Z"));
  generateTextCompletionImpl = async () => "texto base";
});

afterEach(() => {
  setSystemTime();
});

describe("shouldUsePastTense", () => {
  test("past tense when the IG post is older than a week", () => {
    expect(shouldUsePastTense("", "2026-09-01T09:00:00.000Z")).toBe(true);
  });

  test("present tense for a fresh post without dates", () => {
    expect(shouldUsePastTense("", "2026-09-16T08:00:00.000Z")).toBe(false);
  });

  test("past tense when a caption date (10 de abril) has passed", () => {
    expect(shouldUsePastTense("Concierto el 10 de abril en Madrid", "2026-09-16T08:00:00.000Z")).toBe(true);
  });

  test("present tense when the caption date is still ahead", () => {
    expect(shouldUsePastTense("Concierto el 10 de diciembre en Madrid", "2026-09-16T08:00:00.000Z")).toBe(false);
  });

  test("understands dd/mm and dd.mm dates", () => {
    expect(shouldUsePastTense("Nos vemos el 10/04", "2026-09-16T08:00:00.000Z")).toBe(true);
    expect(shouldUsePastTense("Nos vemos el 10.12", "2026-09-16T08:00:00.000Z")).toBe(false);
  });

  test("ignores impossible dates", () => {
    expect(shouldUsePastTense("Evento el 40/13", "2026-09-16T08:00:00.000Z")).toBe(false);
  });
});

describe("buildTweetPrompt", () => {
  test("includes the blog data, caption and the URL budget rule", () => {
    const prompt = buildTweetPrompt(post, "Carteles en Madrid", "2026-09-01T09:00:00.000Z");
    expect(prompt).toContain("BLOG POST TITLE: Concierto en Madrid");
    expect(prompt).toContain("https://urbanstylepublicity.com/blog/concierto-en-madrid");
    expect(prompt).toContain("Carteles en Madrid");
    expect(prompt).toContain("at most 250 characters");
    expect(prompt).toContain("PAST tense");
  });

  test("switches to present tense for a current campaign", () => {
    const prompt = buildTweetPrompt(post, "Carteles en Madrid", "2026-09-16T08:00:00.000Z");
    expect(prompt).toContain("PRESENT tense");
  });
});

describe("generateTweets", () => {
  test("appends the URL and strips URLs the model sneaks in", async () => {
    generateTextCompletionImpl = async () => "  Texto con enlace https://otro.test/x  ";
    const tweets = await generateTweets(post, "caption", "2026-09-16T08:00:00.000Z");
    expect(tweets.gemini).toBe("Texto con enlace https://urbanstylepublicity.com/blog/concierto-en-madrid");
    expect(tweets.deepseek).toBe(tweets.gemini);
  });

  test("caps the body at 257 chars before appending the URL", async () => {
    generateTextCompletionImpl = async () => "a".repeat(400);
    const tweets = await generateTweets(post, "", "2026-09-16T08:00:00.000Z");
    const body = tweets.gemini!.replace(" https://urbanstylepublicity.com/blog/concierto-en-madrid", "");
    expect(body.length).toBeLessThanOrEqual(257);
  });

  test("rejects when both providers fail", async () => {
    generateTextCompletionImpl = async () => {
      throw new Error("LLM down");
    };
    await expect(generateTweets(post, "", "2026-09-16T08:00:00.000Z")).rejects.toThrow(
      "Both LLMs failed to generate a tweet",
    );
  });
});

describe("tweet helpers", () => {
  test("tweetUrlFor builds the canonical blog URL", () => {
    expect(tweetUrlFor(post)).toBe("https://urbanstylepublicity.com/blog/concierto-en-madrid");
  });

  test("formatTweetPreview includes the character count", () => {
    expect(formatTweetPreview("hola")).toBe("🐦 *Tweet (4 caracteres):*\n\nhola");
  });
});
