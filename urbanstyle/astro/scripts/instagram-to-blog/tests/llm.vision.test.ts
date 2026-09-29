/**
 * Vision wiring for article generation: each provider receives the actual
 * Instagram image (Gemini inlineData, DeepSeek OpenAI-style image_url) plus
 * the caption in the prompt. A Gemini outage must never prevent the DeepSeek
 * article — there is no cross-provider describeImage gate anymore.
 */
import "./helpers/silence";
import { afterEach, beforeEach, describe, expect, mock, test } from "bun:test";
import { fileURLToPath } from "node:url";
import { join } from "node:path";

const ROOT = join(fileURLToPath(new URL(".", import.meta.url)), "..");

interface ChatArgs {
  messages: { role: string; content: unknown }[];
}
type ContentPart = { type: string; text?: string; image_url?: { url: string } };

const chatCalls: ChatArgs[] = [];
const geminiCalls: { input: unknown }[] = [];
let geminiError: Error | null = null;

const ARTICLE_JSON = JSON.stringify({
  title: "Título",
  description: "Descripción",
  content: "Contenido",
  tags: ["tag"],
  category: "Categoría",
});

mock.module("openai", () => ({
  default: class {
    chat = {
      completions: {
        create: async (args: ChatArgs) => {
          chatCalls.push(args);
          return { choices: [{ message: { content: ARTICLE_JSON } }] };
        },
      },
    };
  },
}));

mock.module("@google/generative-ai", () => ({
  GoogleGenerativeAI: class {
    getGenerativeModel() {
      return {
        generateContent: async (input: unknown) => {
          geminiCalls.push({ input });
          if (geminiError) throw geminiError;
          return { response: { text: () => ARTICLE_JSON } };
        },
      };
    }
  },
}));

mock.module(join(ROOT, "instructions.ts"), () => ({
  loadInstructions: async () => [],
  formatInstructions: () => "",
  markInstructionsApplied: async () => {},
}));

const { generateArticle, generateArticles, generateDeepseekArticle } = await import("../llm");

const realFetch = globalThis.fetch;
const IMAGE_BYTES = new Uint8Array([0xff, 0xd8, 0xff, 0xe0, 0x00, 0x10]);

function installFetch(status = 200): void {
  globalThis.fetch = (async () =>
    new Response(status === 200 ? IMAGE_BYTES : null, {
      status,
      headers: status === 200 ? { "content-type": "image/jpeg" } : {},
    })) as unknown as typeof fetch;
}

const post = {
  id: "18000000000000001",
  caption: "Pegada de carteles en Madrid",
  mediaUrl: "https://cdn.test/post.jpg",
  timestamp: "2026-09-01T09:00:00.000Z",
  mediaType: "IMAGE",
};

beforeEach(() => {
  chatCalls.length = 0;
  geminiCalls.length = 0;
  geminiError = null;
  installFetch();
});

afterEach(() => {
  globalThis.fetch = realFetch;
});

describe("DeepSeek vision", () => {
  test("attaches the Instagram image and keeps the caption in the prompt", async () => {
    await generateDeepseekArticle(post);

    expect(chatCalls.length).toBe(1);
    const content = chatCalls[0]!.messages[1]!.content as ContentPart[];
    expect(Array.isArray(content)).toBe(true);
    const image = content.find((p) => p.type === "image_url");
    expect(image?.image_url?.url.startsWith("data:image/jpeg;base64,")).toBe(true);
    const text = content.find((p) => p.type === "text");
    expect(text?.text).toContain("Instagram caption: Pegada de carteles en Madrid");
  });

  test("still generates text-only when the image cannot be fetched", async () => {
    installFetch(404);

    const article = await generateDeepseekArticle(post);

    expect(article.title).toBe("Título");
    const content = chatCalls[0]!.messages[1]!.content as ContentPart[];
    expect(Array.isArray(content)).toBe(true);
    expect(content.some((p) => p.type === "image_url")).toBe(false);
  });
});

describe("generateArticles without the describeImage gate", () => {
  test(
    "returns the DeepSeek article when Gemini is unavailable",
    async () => {
      geminiError = new Error("Gemini unavailable (400 invalid request)");

      const articles = await generateArticles(post);

      expect(articles.deepseek?.title).toBe("Título");
      expect(articles.gemini).toBeUndefined();
    },
    // Gemini's withRetries backs off 2s+4s on non-503 failures before giving up.
    15_000,
  );

  test("generates both articles when both providers work", async () => {
    const articles = await generateArticles(post);

    expect(articles.gemini?.title).toBe("Título");
    expect(articles.deepseek?.title).toBe("Título");
  });
});

describe("generateArticle (single provider)", () => {
  test("regenerating with DeepSeek never touches Gemini", async () => {
    geminiError = new Error("Gemini unavailable (400 invalid request)");

    const article = await generateArticle(post, undefined, "deepseek");

    expect(article.title).toBe("Título");
    expect(geminiCalls.length).toBe(0);
  });

  test("Gemini receives an inlineData image and no extra description call", async () => {
    const article = await generateArticle(post, undefined, "gemini");

    expect(article.title).toBe("Título");
    expect(geminiCalls.length).toBe(1);
    const parts = geminiCalls[0]!.input as { inlineData?: { mimeType: string } }[];
    expect(parts.some((p) => p.inlineData?.mimeType === "image/jpeg")).toBe(true);
  });
});
