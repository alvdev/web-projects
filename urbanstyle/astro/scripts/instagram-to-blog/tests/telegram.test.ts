/**
 * Telegram approval photos: the image must be uploaded from our server first
 * (Telegram cannot reliably fetch Instagram CDN URLs), then the URL, then
 * text-only — never a silent text fallback. The preview must also be safe to
 * truncate in Telegram's legacy Markdown.
 */
import "./helpers/silence";
import { afterEach, beforeEach, describe, expect, mock, test } from "bun:test";
import type { PendingEntry } from "../types";

interface SentPhoto {
  chatId: number;
  photo: unknown;
  opts: { caption?: string };
}

interface SentMessage {
  chatId: number;
  text: string;
}

const sentPhotos: SentPhoto[] = [];
const sentMessages: SentMessage[] = [];
let sendPhotoCalls = 0;
let sendPhotoError: Error | null = null;

mock.module("grammy", () => ({
  Bot: class {
    api = {
      sendPhoto: async (chatId: number, photo: unknown, opts: { caption?: string }) => {
        sendPhotoCalls += 1;
        if (sendPhotoError) throw sendPhotoError;
        sentPhotos.push({ chatId, photo, opts });
      },
      sendMessage: async (chatId: number, text: string) => {
        sentMessages.push({ chatId, text });
      },
    };
    catch() {}
  },
  InlineKeyboard: class {
    text(): this {
      return this;
    }
    row(): this {
      return this;
    }
    url(): this {
      return this;
    }
  },
  InputFile: class {
    constructor(
      public data: Uint8Array,
      public filename: string,
    ) {}
  },
}));

const { fetchPhotoInput, formatEntryPreview, notifyTelegram } = await import("../telegram");

const realFetch = globalThis.fetch;
const IMAGE_BYTES = new Uint8Array([0xff, 0xd8, 0xff, 0xe0, 0x00, 0x10]);
let fetchImpl: () => Response = () =>
  new Response(IMAGE_BYTES, { status: 200, headers: { "content-type": "image/jpeg" } });

function installFetch(): void {
  globalThis.fetch = (async () => fetchImpl()) as unknown as typeof fetch;
}

function makeEntry(): PendingEntry {
  const article = {
    title: "Título de prueba",
    description: "Descripción de prueba",
    content: "Mira [esta imagen](./header.jpg) y *esto* es el contenido del artículo.",
    tags: ["tag"],
    category: "Categoría",
  };
  return {
    id: "100",
    article,
    post: {
      id: "100",
      caption: "carteles en Madrid",
      mediaUrl: "https://cdn.test/post.jpg",
      timestamp: "2026-09-01T09:00:00.000Z",
      mediaType: "CAROUSEL_ALBUM",
    },
    prepared: {
      ...article,
      slug: "slug-de-prueba",
      pubDate: "01-09-2026 09:00",
      updatedDate: "01-09-2026 09:00",
      basePath: "",
      media_url: "https://cdn.test/post.jpg",
      igMediaId: "100",
      mdxPath: "",
      imagePath: "",
    },
    status: "pending",
    feedback: null,
    createdAt: "2026-09-01T09:00:00.000Z",
    attempts: 1,
    articles: { gemini: article, deepseek: { ...article, title: "Título DeepSeek" } },
  };
}

beforeEach(() => {
  sentPhotos.length = 0;
  sentMessages.length = 0;
  sendPhotoCalls = 0;
  sendPhotoError = null;
  process.env.TELEGRAM_BOT_TOKEN = "test-token";
  fetchImpl = () => new Response(IMAGE_BYTES, { status: 200, headers: { "content-type": "image/jpeg" } });
  installFetch();
});

afterEach(() => {
  globalThis.fetch = realFetch;
});

describe("fetchPhotoInput", () => {
  test("returns an upload for an image response", async () => {
    const file = await fetchPhotoInput("https://cdn.test/post.jpg");

    expect(file?.filename).toBe("post.jpg");
  });

  test("returns null on an HTTP error", async () => {
    fetchImpl = () => new Response(null, { status: 404 });

    expect(await fetchPhotoInput("https://cdn.test/post.jpg")).toBeNull();
  });

  test("returns null when the fetch throws", async () => {
    fetchImpl = () => {
      throw new Error("network down");
    };

    expect(await fetchPhotoInput("https://cdn.test/post.jpg")).toBeNull();
  });
});

describe("notifyTelegram approval photo", () => {
  test("sends our upload instead of the Instagram URL", async () => {
    await notifyTelegram("approval", makeEntry(), 1);

    expect(sentPhotos.length).toBe(1);
    expect((sentPhotos[0]!.photo as { filename?: string }).filename).toBe("post.jpg");
  });

  test("falls back to the URL when our fetch fails", async () => {
    fetchImpl = () => new Response(null, { status: 404 });

    await notifyTelegram("approval", makeEntry(), 1);

    expect(sentPhotos.length).toBe(1);
    expect(sentPhotos[0]!.photo).toBe("https://cdn.test/post.jpg");
  });

  test("falls back to text when the photo cannot be sent at all", async () => {
    sendPhotoError = new Error("Bad Request: can't parse entities");

    await notifyTelegram("approval", makeEntry(), 1);

    expect(sendPhotoCalls).toBe(2); // upload attempt + URL attempt
    expect(sentPhotos.length).toBe(0);
    expect(sentMessages.length).toBe(1);
    expect(sentMessages[0]!.text).toContain("Título de prueba");
  });
});

describe("formatEntryPreview", () => {
  test("strips [ and ] from the content preview so truncation cannot break Markdown", () => {
    const preview = formatEntryPreview(makeEntry());

    expect(preview.includes("[")).toBe(false);
    expect(preview.includes("]")).toBe(false);
    expect(preview).toContain("esta imagen");
  });
});
