/**
 * Shared harness for bot.ts tests: registers every external dependency as an
 * in-memory mock BEFORE bot.ts is imported, so importing the bot has no side
 * effects (no Telegram polling, no FS, no network).
 *
 * Usage:
 *   import { h, makeCtx, makePublished } from "./helpers/harness";
 *   const bot = await h.importBot();
 */
import { mock } from "bun:test";
import { fileURLToPath } from "node:url";
import { join } from "node:path";
import type { LlmArticle, NewPost, PendingEntry, PendingState, PreparedPost, PublishedEntry } from "../../types";

const ROOT = join(fileURLToPath(new URL(".", import.meta.url)), "..", "..");

/** Absolute path to scripts/instagram-to-blog (for importing sibling modules). */
export const SCRIPT_ROOT = ROOT;

export interface CapturedEmail {
  subject: string;
  body: string;
}

export interface FakeCtx {
  chat: { id: number };
  match: string[];
  session: Record<string, unknown>;
  replies: { text: string; opts?: unknown }[];
  edits: { chatId: number; messageId: number; text: string; opts?: unknown }[];
  answers: string[];
  markupEdits: unknown[];
  reply(text: string, opts?: unknown): Promise<{ message_id: number }>;
  answerCallbackQuery(opts?: { text?: string }): Promise<void>;
  api: {
    editMessageText(chatId: number, messageId: number, text: string, opts?: unknown): Promise<void>;
    editMessageReplyMarkup(chatId: number, messageId: number, opts?: unknown): Promise<void>;
  };
}

interface Registered {
  pattern: RegExp;
  handler: (ctx: FakeCtx) => Promise<void> | void;
}

export const emails: CapturedEmail[] = [];
export const written: PreparedPost[] = [];
export const bufferCalls: { channelId: string; text: string; imageUrl?: string; facebookType?: string }[] = [];
export const fbCalls: { message: string; link: string }[] = [];
export const gbpCalls: { text: string; imageUrl?: string }[] = [];
export const linkedinCalls: { text: string; imageUrl?: string }[] = [];
export const gitCalls: { fn: string; slug: string }[] = [];
export const telegramCalls: { kind: string; payload: unknown }[] = [];
export const publishOrder: string[] = [];
export const deployCalls = { buildSite: 0, uploadDist: 0, removeRemoteDir: [] as string[] };
export const xverifyCalls = { verifyTweetHandles: 0, findOfficialHandle: 0 };

/** Mutable per-test implementations (override with h.xxxImpl = ...). */
export const h = {
  state: null as PendingState | null,
  writePostFilesImpl: async (_p: PreparedPost): Promise<void> => {},
  uploadDistImpl: async (): Promise<{ uploaded: number; skipped: number; total: number; pending: number }> => ({
    uploaded: 3,
    skipped: 1,
    total: 4,
    pending: 3,
  }),
  buildSiteImpl: async (): Promise<void> => {},
  createPostImpl: async (
    channelId: string,
    _text: string,
    _imageUrl?: string,
    _facebookType?: string,
  ): Promise<{ id: string; externalLink?: string }> => ({
    id: `buf-${channelId}`,
    externalLink: `https://buffer.test/${channelId}`,
  }),
  createFbPostImpl: async (_message: string, _link: string): Promise<{ id: string; externalLink: string }> => {
    throw new Error("Graph API not configured in test");
  },
  createGbpPostImpl: async (_text: string, _imageUrl?: string): Promise<{ id: string; externalLink?: string }> => ({
    id: "gbp-1",
    externalLink: "https://gbp.test/post/1",
  }),
  createLinkedInPostImpl: async (_text: string, _imageUrl?: string): Promise<{ id: string; externalLink?: string }> => ({
    id: "li-1",
    externalLink: "https://linkedin.test/post/1",
  }),
  translateImpl: async (_slug: string): Promise<string[]> => {
    throw new Error("translations disabled in tests");
  },
  commitAndPushImpl: (_slug: string): void => {},
  cachedHandlesCoverImpl: (_tweet: string, _cached?: unknown): boolean => true,
  verifyTweetHandlesImpl: async (_tweet: string): Promise<{ handle: string; status: string; verifiedAt: string }[]> => [],
  generateTweetsImpl: async (_post: PreparedPost): Promise<{ gemini?: string; deepseek?: string }> => ({
    gemini: "texto generado sin url",
  }),
  generateFbTextsImpl: async (): Promise<{ gemini?: string; deepseek?: string }> => ({ gemini: "texto fb" }),
};

const registered: Registered[] = [];
let statusMessageId = 100;

// ---- fake grammy ----

class FakeInlineKeyboard {
  text(): this {
    return this;
  }
  row(): this {
    return this;
  }
  url(): this {
    return this;
  }
}

class FakeBot {
  use(): this {
    return this;
  }
  callbackQuery(pattern: RegExp, handler: (ctx: FakeCtx) => Promise<void> | void): this {
    registered.push({ pattern, handler });
    return this;
  }
  command(): this {
    return this;
  }
  on(): this {
    return this;
  }
  catch(): void {}
  start(): Promise<void> {
    return Promise.resolve();
  }
}

function fakeSession(): () => void {
  return () => {};
}

mock.module("grammy", () => ({
  Bot: FakeBot,
  Context: class {},
  InlineKeyboard: FakeInlineKeyboard,
  session: fakeSession,
}));

// ---- module mocks ----

mock.module(join(ROOT, "state.ts"), () => ({
  STATE_FILE: "/tmp/urbanstyle-test/.pending.json",
  loadState: async (): Promise<PendingState> => h.state as PendingState,
  saveState: async (state: PendingState): Promise<void> => {
    h.state = state;
  },
  bumpCache: (): void => {},
}));

mock.module(join(ROOT, "mailer.ts"), () => ({
  sendAlert: async (subject: string, body: string): Promise<void> => {
    emails.push({ subject, body });
  },
}));

mock.module(join(ROOT, "deploy.ts"), () => ({
  PROJECT_ROOT: "/tmp/urbanstyle-test/project",
  buildSite: async (): Promise<void> => {
    deployCalls.buildSite++;
    return h.buildSiteImpl();
  },
  uploadDist: async (): Promise<{ uploaded: number; skipped: number; total: number; pending: number }> => {
    deployCalls.uploadDist++;
    return h.uploadDistImpl();
  },
  removeRemoteDir: async (dir: string): Promise<void> => {
    deployCalls.removeRemoteDir.push(dir);
  },
}));

mock.module(join(ROOT, "content.ts"), () => ({
  BLOG_ROOT: "/tmp/urbanstyle-test/blog",
  preparePost: (article: LlmArticle, post: NewPost): PreparedPost => ({
    title: article.title,
    description: article.description,
    content: article.content,
    tags: article.tags,
    category: article.category,
    slug: "test-post",
    pubDate: "01-09-2026 10:00",
    updatedDate: "01-09-2026 10:00",
    basePath: "/tmp/urbanstyle-test/blog/test-post",
    media_url: post.mediaUrl,
    igMediaId: post.id,
    mdxPath: "/tmp/urbanstyle-test/blog/test-post/index.mdx",
    imagePath: "/tmp/urbanstyle-test/blog/test-post/header.jpg",
  }),
  writePostFiles: async (p: PreparedPost): Promise<void> => {
    written.push(p);
    return h.writePostFilesImpl(p);
  },
}));

mock.module(join(ROOT, "llm.ts"), () => ({
  generateArticle: async (): Promise<LlmArticle> => ({
    title: "Título de prueba",
    description: "Descripción de prueba",
    content: "Contenido de prueba",
    tags: ["tag"],
    category: "Categoría",
  }),
  generateArticles: async (): Promise<{ gemini?: LlmArticle; deepseek?: LlmArticle }> => ({
    gemini: { title: "Título Gemini", description: "Descripción", content: "Contenido", tags: ["t"], category: "C" },
    deepseek: { title: "Título DeepSeek", description: "Descripción", content: "Contenido", tags: ["t"], category: "C" },
  }),
  generateTextCompletion: async (): Promise<string> => "texto llm",
}));

mock.module(join(ROOT, "tweet.ts"), () => ({
  generateTweets: async (post: PreparedPost): Promise<{ gemini?: string; deepseek?: string }> => h.generateTweetsImpl(post),
  buildTweetPrompt: (): string => "prompt de tweet",
}));

mock.module(join(ROOT, "instructions.ts"), () => ({
  addInstruction: async (): Promise<{ id: string }> => ({ id: "ins-1" }),
  loadInstructions: async (): Promise<unknown[]> => [],
  removeInstruction: async (): Promise<void> => {},
}));

mock.module(join(ROOT, "translate.ts"), () => ({
  translatePostBySlug: async (slug: string): Promise<string[]> => h.translateImpl(slug),
}));

mock.module(join(ROOT, "telegram.ts"), () => ({
  notifyTelegram: async (kind: string, payload: unknown): Promise<void> => {
    telegramCalls.push({ kind, payload });
  },
  escMarkdown: (text: string): string => text.replace(/([_*[\]`])/g, "\\$1"),
  escHtml: (text: string): string =>
    text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;"),
  mdToHtml: (md: string): string => md,
}));

mock.module(join(ROOT, "gitSync.ts"), () => ({
  commitAndPushBlogPost: (slug: string): void => {
    gitCalls.push({ fn: "blog", slug });
    h.commitAndPushImpl(slug);
  },
  commitAndPushTranslations: (slug: string): void => {
    gitCalls.push({ fn: "translations", slug });
    h.commitAndPushImpl(slug);
  },
}));

mock.module(join(ROOT, "social", "xverify.ts"), () => ({
  cachedHandlesCover: (tweet: string, cached?: unknown): boolean => h.cachedHandlesCoverImpl(tweet, cached),
  verifyTweetHandles: async (tweet: string): Promise<{ handle: string; status: string; verifiedAt: string }[]> => {
    xverifyCalls.verifyTweetHandles++;
    return h.verifyTweetHandlesImpl(tweet);
  },
  findOfficialHandle: async (): Promise<null> => {
    xverifyCalls.findOfficialHandle++;
    return null;
  },
  findArtistHandle: async (): Promise<null> => null,
  extractHandles: (text: string): string[] =>
    (text.match(/@[A-Za-z0-9_.]+/g) ?? []).map((m) => m.slice(1)),
  verifyHandle: async (handle: string): Promise<{ handle: string; status: string; verifiedAt: string }> => ({
    handle,
    status: "verified",
    verifiedAt: new Date().toISOString(),
  }),
  formatHandleStatus: (): string => "",
  dumpKnownArtistHandles: (): Record<string, string> => ({}),
  seedKnownArtistHandles: (): void => {},
}));

mock.module(join(ROOT, "social", "websearch.ts"), () => ({
  textsRelate: (): boolean => false,
  searchProfile: async (): Promise<unknown[]> => [],
}));

mock.module(join(ROOT, "social", "fbverify.ts"), () => ({
  suggestFbPages: async (): Promise<unknown[]> => [],
  isValidFbPageUrl: (url: string): boolean => url.startsWith("https://"),
  findOfficialFbPage: async (): Promise<null> => null,
  resolveFbPageFollowers: async (): Promise<null> => null,
  seedKnownFbPages: (): void => {},
  dumpKnownFbPages: (): Record<string, unknown> => ({}),
}));

mock.module(join(ROOT, "social", "fbpost.ts"), () => ({
  generateFbTexts: async (): Promise<{ gemini?: string; deepseek?: string }> => h.generateFbTextsImpl(),
}));

mock.module(join(ROOT, "social", "buffer.ts"), () => ({
  getXChannel: async (): Promise<{ id: string; name: string }> => ({ id: "xch", name: "X test" }),
  getFbChannel: async (): Promise<{ id: string; name: string }> => ({ id: "fbch", name: "FB test" }),
  createPost: async (
    channelId: string,
    text: string,
    imageUrl?: string,
    facebookType?: string,
  ): Promise<{ id: string; externalLink?: string }> => {
    bufferCalls.push({ channelId, text, imageUrl, facebookType });
    publishOrder.push(channelId);
    return h.createPostImpl(channelId, text, imageUrl, facebookType);
  },
}));

mock.module(join(ROOT, "social", "facebook.ts"), () => ({
  createFbPost: async (message: string, link: string): Promise<{ id: string; externalLink: string }> => {
    fbCalls.push({ message, link });
    publishOrder.push("facebook-graph");
    return h.createFbPostImpl(message, link);
  },
  deleteFbPost: async (): Promise<void> => {},
  resolvePageId: async (): Promise<null> => null,
}));

mock.module(join(ROOT, "social", "gbp.ts"), () => ({
  getGbpChannel: async (): Promise<{ id: string; name: string }> => ({ id: "gbpch", name: "GBP test" }),
  createGbpPost: async (text: string, imageUrl?: string): Promise<{ id: string; externalLink?: string }> => {
    gbpCalls.push({ text, imageUrl });
    publishOrder.push("gbp");
    return h.createGbpPostImpl(text, imageUrl);
  },
  stripMentions: (text: string): string => text.replace(/@[A-Za-z0-9_.]+/g, "").replace(/\s+/g, " ").trim(),
}));

mock.module(join(ROOT, "social", "linkedin.ts"), () => ({
  getLinkedInChannel: async (): Promise<{ id: string; name: string }> => ({ id: "lich", name: "LinkedIn test" }),
  createLinkedInPost: async (text: string, imageUrl?: string): Promise<{ id: string; externalLink?: string }> => {
    linkedinCalls.push({ text, imageUrl });
    publishOrder.push("linkedin");
    return h.createLinkedInPostImpl(text, imageUrl);
  },
}));

// ---- reset between tests ----

export function resetHarness(): void {
  emails.length = 0;
  written.length = 0;
  bufferCalls.length = 0;
  fbCalls.length = 0;
  gbpCalls.length = 0;
  linkedinCalls.length = 0;
  gitCalls.length = 0;
  telegramCalls.length = 0;
  publishOrder.length = 0;
  deployCalls.buildSite = 0;
  deployCalls.uploadDist = 0;
  deployCalls.removeRemoteDir.length = 0;
  xverifyCalls.verifyTweetHandles = 0;
  xverifyCalls.findOfficialHandle = 0;
  h.state = { pending: [], skippedIds: [], published: [] };
  h.writePostFilesImpl = async (): Promise<void> => {};
  h.uploadDistImpl = async (): Promise<{ uploaded: number; skipped: number; total: number; pending: number }> => ({
    uploaded: 3,
    skipped: 1,
    total: 4,
    pending: 3,
  });
  h.createPostImpl = async (channelId: string): Promise<{ id: string; externalLink?: string }> => ({
    id: `buf-${channelId}`,
    externalLink: `https://buffer.test/${channelId}`,
  });
  h.createFbPostImpl = async (): Promise<{ id: string; externalLink: string }> => {
    throw new Error("Graph API not configured in test");
  };
  h.createGbpPostImpl = async (): Promise<{ id: string; externalLink?: string }> => ({
    id: "gbp-1",
    externalLink: "https://gbp.test/post/1",
  });
  h.createLinkedInPostImpl = async (): Promise<{ id: string; externalLink?: string }> => ({
    id: "li-1",
    externalLink: "https://linkedin.test/post/1",
  });
  h.translateImpl = async (): Promise<string[]> => {
    throw new Error("translations disabled in tests");
  };
  h.commitAndPushImpl = () => {};
  h.cachedHandlesCoverImpl = (): boolean => true;
  h.verifyTweetHandlesImpl = async (): Promise<{ handle: string; status: string; verifiedAt: string }[]> => [];
  h.generateTweetsImpl = async (): Promise<{ gemini?: string; deepseek?: string }> => ({ gemini: "texto generado sin url" });
  h.generateFbTextsImpl = async (): Promise<{ gemini?: string; deepseek?: string }> => ({ gemini: "texto fb" });
  statusMessageId = 100;
}

// NOTE: resetHarness is intentionally NOT registered here. Bun shares the
// module graph across test files, so each test file must call
// `beforeEach(resetHarness)` itself.

// ---- bot import + handler access ----

let botModule: typeof import("../../bot") | null = null;

export async function importBot(): Promise<typeof import("../../bot")> {
  if (botModule) return botModule;
  process.env.TELEGRAM_BOT_TOKEN = "test-token";
  process.env.TELEGRAM_CHAT_ID = "1";
  const mod = await import(join(ROOT, "bot.ts"));
  botModule = mod;
  return mod;
}

export function getHandler(source: string): (ctx: FakeCtx) => Promise<void> | void {
  const found = registered.find((r) => r.pattern.source === source);
  if (!found) throw new Error(`callback handler not registered: ${source}`);
  return found.handler;
}

// ---- fixtures ----

export function makeCtx(match: string[]): FakeCtx {
  const replies: { text: string; opts?: unknown }[] = [];
  const edits: { chatId: number; messageId: number; text: string; opts?: unknown }[] = [];
  const answers: string[] = [];
  const markupEdits: unknown[] = [];
  const ctx: FakeCtx = {
    chat: { id: 1 },
    match,
    session: {},
    replies,
    edits,
    answers,
    markupEdits,
    reply: async (text: string, opts?: unknown) => {
      replies.push({ text, opts });
      return { message_id: statusMessageId++ };
    },
    answerCallbackQuery: async (opts?: { text?: string }) => {
      if (opts?.text) answers.push(opts.text);
    },
    api: {
      editMessageText: async (chatId: number, messageId: number, text: string, opts?: unknown) => {
        edits.push({ chatId, messageId, text, opts });
      },
      editMessageReplyMarkup: async (chatId: number, messageId: number, opts?: unknown) => {
        markupEdits.push({ chatId, messageId, opts });
      },
    },
  };
  return ctx;
}

export function makePrepared(): PreparedPost {
  return {
    title: "Test Post",
    description: "Descripción de prueba",
    content: "Contenido",
    tags: ["tag"],
    category: "Categoría",
    slug: "test-post",
    pubDate: "01-09-2026 10:00",
    updatedDate: "01-09-2026 10:00",
    basePath: "/tmp/urbanstyle-test/blog/test-post",
    media_url: "https://example.test/img.jpg",
    igMediaId: "100",
    mdxPath: "/tmp/urbanstyle-test/blog/test-post/index.mdx",
    imagePath: "/tmp/urbanstyle-test/blog/test-post/header.jpg",
  };
}

export function makePending(overrides: Partial<PendingEntry> = {}): PendingEntry {
  return {
    id: "100",
    article: { title: "Test Post", description: "Descripción", content: "Contenido", tags: ["tag"], category: "Categoría" },
    post: { id: "100", caption: "caption", mediaUrl: "https://example.test/img.jpg", timestamp: "2026-09-01T09:00:00.000Z", mediaType: "IMAGE" },
    prepared: makePrepared(),
    status: "pending",
    feedback: null,
    createdAt: "2026-09-01T09:00:00.000Z",
    attempts: 1,
    ...overrides,
  };
}

export function makePublished(social: PublishedEntry["social"] = {}): PublishedEntry {
  return {
    id: "100",
    slug: "test-post",
    title: "Test Post",
    publishedAt: "2026-09-01T10:00:00.000Z",
    caption: "caption",
    postTimestamp: "2026-09-01T09:00:00.000Z",
    mediaType: "IMAGE",
    social,
  };
}
