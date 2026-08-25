import { Bot, Context, InlineKeyboard, session, type SessionFlavor } from "grammy";
import { loadState, saveState, bumpCache } from "./state";
import { writePostFiles, BLOG_ROOT } from "./content";
import { buildSite, uploadDist, removeRemoteDir } from "./deploy";
import { generateArticle, generateArticles, generateTextCompletion } from "./llm";
import { preparePost } from "./content";
import { generateTweets, buildTweetPrompt } from "./tweet";
import { addInstruction, loadInstructions, removeInstruction } from "./instructions";
import { sendAlert } from "./mailer";
import { notifyTelegram, escMarkdown, mdToHtml, escHtml } from "./telegram";
import type { PendingEntry, PendingState, PreparedPost, PublishedEntry } from "./types";
import { rm } from "node:fs/promises";
import { join } from "node:path";

interface SessionData {
  awaitingFeedbackFor?: string;
  awaitingEditFor?: { postId: string; field: "title" | "desc" | "content" | "tweet" | "fbtext" | "xhandles" | "fbhandles" };
  fbSuggestions?: Record<string, { handle: string; pageName: string; pageUrl?: string; source?: "web" | "gemini" }[]>;
  tweetCandidates?: Record<string, { gemini?: string; deepseek?: string }>;
}

type MyContext = Context & SessionFlavor<SessionData>;

const TOKEN = process.env.TELEGRAM_BOT_TOKEN ?? "";
if (!TOKEN) {
  console.error("TELEGRAM_BOT_TOKEN not set");
  process.exit(1);
}

const ALLOWED_CHAT_IDS = new Set<string>(
  [process.env.TELEGRAM_CHAT_ID].filter(Boolean).map(String),
);

const bot = new Bot<MyContext>(TOKEN);
bot.use(session({ initial: (): SessionData => ({}) }));

function isAllowed(ctx: MyContext): boolean {
  return ALLOWED_CHAT_IDS.size === 0 || ALLOWED_CHAT_IDS.has(String(ctx.chat?.id));
}

/**
 * The bot runs long-lived while index.ts/watcher.ts write the state file from
 * separate processes. Always bypass the in-process cache so Telegram actions
 * see the freshest state.
 */
async function freshState(): Promise<PendingState> {
  bumpCache();
  return loadState();
}

async function publishEntry(entry: PendingEntry, state: PendingState, ctx: MyContext): Promise<void> {
  const chatId = ctx.chat?.id ?? state.chatId ?? 0;
  const statusMsg = await ctx.reply(`⏳ Publicando "${entry.prepared.title}"...`);

  const update = async (text: string) => {
    try {
      await ctx.api.editMessageText(chatId, statusMsg.message_id, text, { parse_mode: "Markdown" });
    } catch {
      // message may have been edited already; ignore
    }
  };

  try {
    await update(`⏳ Publicando (1/3) — escribiendo MDX e imagen...`);
    await writePostFiles(entry.prepared);

    await update(`⏳ Publicando (2/3) — compilando sitio...`);
    await buildSite();

    await update(`⏳ Publicando (3/3) — subiendo archivos...`);
    const { uploaded, skipped } = await uploadDist((done, pendingCount) => {
      void update(`⏳ Publicando (3/3) — subiendo archivos: ${done}/${pendingCount}`);
    });
    console.log(`[bot] published ${entry.prepared.mdxPath}`);

    state.pending = state.pending.filter((e) => e.id !== entry.id);
    if (!state.lastProcessedId || BigInt(entry.id) > BigInt(state.lastProcessedId)) {
      state.lastProcessedId = entry.id;
    }
    state.published = state.published.filter((e) => e.id !== entry.id);
    state.published.push({
      id: entry.id,
      slug: entry.prepared.slug,
      title: entry.prepared.title,
      publishedAt: new Date().toISOString(),
      caption: entry.post.caption,
      postTimestamp: entry.post.timestamp,
      mediaType: entry.post.mediaType,
      social: { x: { status: "queued" } },
    });
    await saveState(state);

    await update(`✅ *Publicado:* ${entry.prepared.title}\n\n/blog/${entry.prepared.slug} (${uploaded} archivos subidos, ${skipped} sin cambios)`);

    const publishedKeyboard = new InlineKeyboard()
      .text("🗑 Eliminar", `remove:${entry.id}`)
      .text("▶️ Publicar en redes", `social:${entry.id}`);
    await ctx.api.editMessageReplyMarkup(chatId, statusMsg.message_id, { reply_markup: publishedKeyboard });

    await sendAlert(`[Urban Sync] Publicado: ${entry.prepared.title}`, [
      `Título: ${entry.prepared.title}`,
      `URL: /blog/${entry.prepared.slug}`,
      `Fecha: ${entry.prepared.pubDate}`,
      `Uploaded: ${uploaded}, unchanged: ${skipped}`,
    ].join("\n"));
  } catch (err) {
    console.error("[bot] publish failed:", err);
    await update(`❌ Error al publicar: ${(err as Error).message}`);
    await sendAlert("[Urban Sync] Error al publicar", (err as Error).message);
  }
}

// ---- Remove / social queue after publish ----

async function removePublishedPost(postId: string, ctx: MyContext): Promise<void> {
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  if (!published) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado en publicados" });
    return;
  }

  const chatId = ctx.chat?.id ?? state.chatId ?? 0;
  await ctx.answerCallbackQuery({ text: "Eliminando..." });
  const statusMsg = await ctx.reply(`⏳ Eliminando "${published.title}" del sitio...`);

  try {
    const slug = published.slug;
    const localDir = join(BLOG_ROOT, slug);

    await rm(localDir, { recursive: true, force: true });
    console.log(`[bot] removed local dir ${localDir}`);

    await ctx.api.editMessageText(chatId, statusMsg.message_id, `⏳ Eliminando (2/3) — compilando sitio...`);
    await buildSite();

    await ctx.api.editMessageText(chatId, statusMsg.message_id, `⏳ Eliminando (3/3) — sincronizando producción...`);
    const { uploaded, skipped } = await uploadDist();
    await removeRemoteDir(`/blog/${slug}`);

    state.published = state.published.filter((e) => e.id !== postId);
    if (!state.skippedIds.includes(postId)) state.skippedIds.push(postId);
    await saveState(state);

    await ctx.api.editMessageText(
      chatId,
      statusMsg.message_id,
      `🗑 *Eliminado:* ${published.title}\n\n/blog/${slug} borrado del sitio y de la cola de redes.`,
      { parse_mode: "Markdown" },
    );
    await sendAlert(`[Urban Sync] Eliminado: ${published.title}`, `Slug: /blog/${slug}\nUploaded: ${uploaded}, unchanged: ${skipped}`);
  } catch (err) {
    console.error("[bot] remove failed:", err);
    await ctx.api.editMessageText(chatId, statusMsg.message_id, `❌ Error al eliminar: ${(err as Error).message}`);
    await sendAlert("[Urban Sync] Error al eliminar", (err as Error).message);
  }
}

bot.callbackQuery(/^remove:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  await removePublishedPost(ctx.match[1], ctx);
});

/**
 * Extract the main artist name from the caption: the first meaningful words
 * (up to 3), stripped of hashtags, emojis, URLs, @tokens and separators.
 * Returns progressively shorter candidates ("DEI V llega a" → "DEI V").
 */
function extractArtistName(caption: string): string[] {
  const clean = caption
    .replace(/https?:\/\/\S+/g, " ")
    .replace(/#\w+/g, " ")
    .replace(/@\w+/g, " ")
    .replace(/[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{2B00}-\u{2BFF}\u{FE0F}\u{200D}]/gu, " ")
    .replace(/[\s•·—–\-]+/g, " ")
    .replace(/\s+/g, " ")
    .trim();
  const words = clean
    .split(/\s+/)
    .filter(Boolean)
    // Strip leading/trailing punctuation so "¡DEI V" → "DEI V".
    .map((w) => w.replace(/^[¡¿«“'([\]]+|[.,;:!?»”')\]]+$/g, ""))
    .filter(Boolean);
  const candidates = [words.slice(0, 3).join(" "), words.slice(0, 2).join(" "), words[0]].filter(
    (w): w is string => !!w && w.length >= 2,
  );
  return [...new Set(candidates)];
}

/**
 * Escape text for Telegram Markdown while turning every @handle into a
 * clickable link (with the @ symbol) to its x.com profile. When a whitelist
 * (lowercase handles) is given, ONLY those handles are linked — the rest
 * stay as plain escaped text. Escapes the non-handle pieces; handles are
 * regex-validated and safe inside link text.
 */
function linkifyHandles(text: string, whitelist?: Set<string>): string {
  return text
    .split(/(@[A-Za-z0-9_]{1,15})/g)
    .map((part) => {
      if (/^@[A-Za-z0-9_]{1,15}$/.test(part)) {
        const h = part.slice(1).toLowerCase();
        if (!whitelist || whitelist.has(h)) return `[${part}](https://x.com/${part.slice(1)})`;
      }
      return escMarkdown(part);
    })
    .join("");
}

async function startTweetFlow(published: PublishedEntry, state: PendingState, ctx: MyContext): Promise<void> {
  const xState = published.social.x ?? { status: "queued" };
  published.social.x = xState;

  await ctx.reply(`⏳ Generando propuestas de tweet para *"${published.title}"* (Gemini + DeepSeek)...`, {
    parse_mode: "Markdown",
  });

  try {
    // Rebuild the PreparedPost so tweet.ts has full data (title, desc, slug)
    const pendingEntry = state.pending.find((e) => e.id === published.id);
    const source = pendingEntry?.prepared ?? {
      title: published.title,
      description: "",
      content: "",
      tags: [],
      category: "",
      slug: published.slug,
      pubDate: published.publishedAt,
      basePath: "",
      media_url: "",
      igMediaId: published.id,
      mdxPath: "",
      imagePath: "",
    };

    const tweets = await generateTweets(
      source as PreparedPost,
      published.caption ?? pendingEntry?.post.caption ?? "",
      published.postTimestamp ?? pendingEntry?.post.timestamp,
    );

    // Inject the main artist's @mention when the LLM wrote the plain name
    // ("DEI V" → "@DeiV"), so the artist is always mentioned on X (and, via
    // the FB flow, its Facebook page). The handle is resolved via web search
    // + x.com verification; if it cannot be verified the text stays plain.
    const { findArtistHandle, extractHandles } = await import("./social/xverify");
    const { textsRelate } = await import("./social/websearch");
    const artistCandidates = extractArtistName(published.caption ?? pendingEntry?.post.caption ?? "");
    const captionText = published.caption ?? pendingEntry?.post.caption ?? "";
    const injectArtistMention = async (text?: string): Promise<string | undefined> => {
      if (!text || artistCandidates.length === 0) return text;
      const existing = extractHandles(text).filter((h) => textsRelate(h, artistCandidates[0]));
      if (existing.length > 0) return text;
      for (const artistName of artistCandidates) {
        const idx = text.toLowerCase().indexOf(artistName.toLowerCase());
        if (idx === -1) continue;
        const official = await findArtistHandle(artistName, captionText);
        if (!official?.handle) continue; // try the shorter candidate
        return `${text.slice(0, idx)}@${official.handle}${text.slice(idx + artistName.length)}`.trim();
      }
      return text;
    };
    if (tweets.gemini) tweets.gemini = await injectArtistMention(tweets.gemini);
    if (tweets.deepseek) tweets.deepseek = await injectArtistMention(tweets.deepseek);

    // Persist verified artist-handles found during injection.
    const { dumpKnownArtistHandles } = await import("./social/xverify");
    const dumpedHandles = dumpKnownArtistHandles();
    if (Object.keys(dumpedHandles).length > 0) {
      state.knownXHandles = { ...state.knownXHandles, ...dumpedHandles };
    }

    xState.status = "queued";
    xState.tweet = undefined;
    await saveState(state);

    // Verify every @mention in both versions ONCE. Only existing accounts get
    // a 🔗 profile button; non-existent ones are resolved via the official
    // handle lookup and announced (the actual fix happens at pickTweet).
    const { verifyHandle, formatHandleStatus, findOfficialHandle } = await import("./social/xverify");
    type HandleInfo = Awaited<ReturnType<typeof verifyHandle>>;
    const allHandles = [
      ...new Set([tweets.gemini ?? "", tweets.deepseek ?? ""].flatMap((t) => extractHandles(t))),
    ];
    const statusByHandle = new Map<string, HandleInfo>();
    const corrections: Record<string, string> = {}; // badHandle -> official handle
    const caption = published.caption ?? pendingEntry?.post.caption ?? "";
    for (const h of allHandles) {
      const info = await verifyHandle(h);
      statusByHandle.set(h, info);
      if (info.status !== "verified") {
        const official = await findOfficialHandle(h, caption);
        if (official?.status === "verified") corrections[h] = official.handle;
      }
    }
    const statusLineFor = (t: string): string[] =>
      extractHandles(t)
        .map((h) => statusByHandle.get(h))
        .filter((i): i is HandleInfo => !!i)
        .map((i) => formatHandleStatus(i));

    // Only VERIFIED handles and correction officials get clickable links in
    // the version texts — non-existent mentions stay as plain text.
    const linkWhitelist = new Set([
      ...allHandles.filter((h) => statusByHandle.get(h)?.status === "verified").map((h) => h.toLowerCase()),
      ...Object.values(corrections).map((h) => h.toLowerCase()),
    ]);
    // Persist the exact versions so pickTweet uses the picked text verbatim
    // (no regeneration dropping mentions like @movistararenaes).
    ctx.session.tweetCandidates = {
      ...ctx.session.tweetCandidates,
      [published.id]: { gemini: tweets.gemini, deepseek: tweets.deepseek },
    };

    const lines: string[] = ["Selecciona la versión del tweet:"];
    if (tweets.gemini) {
      lines.push("", `📝 *GEMINI:*\n${linkifyHandles(tweets.gemini, linkWhitelist)}`);
      const statuses = statusLineFor(tweets.gemini);
      if (statuses.length) lines.push("", ...statuses.map((s) => escMarkdown(s)));
    }
    if (tweets.deepseek) {
      lines.push("", `📝 *DEEPSEEK:*\n${linkifyHandles(tweets.deepseek, linkWhitelist)}`);
      const statuses = statusLineFor(tweets.deepseek);
      if (statuses.length) lines.push("", ...statuses.map((s) => escMarkdown(s)));
    }
    if (tweets.gemini && tweets.deepseek && tweets.gemini === tweets.deepseek) {
      lines.push("", "⚠️ Ambas versiones son idénticas.");
    }
    const correctionEntries = Object.entries(corrections);
    if (correctionEntries.length > 0) {
      lines.push(
        "",
        ...correctionEntries.map(([bad, good]) => `❌ @${escMarkdown(bad)} no existe en X → se corregirá a *@${escMarkdown(good)}* al elegir versión.`),
      );
    }

    // Dual picker buttons
    const kb = new InlineKeyboard();
    if (tweets.gemini) kb.text("❤️ Tweet Gemini", `pickTweet:gemini:${published.id}`);
    if (tweets.deepseek) kb.text("💙 Tweet DeepSeek", `pickTweet:deepseek:${published.id}`);

    // Clickable profile links ONLY for accounts that exist (verified handles +
    // resolved official corrections). Non-existent mentions never get a button.
    // Deduplicated case-insensitively (Deivpr == deivpr).
    const buttonHandles = [
      ...new Map(
        [
          ...allHandles.filter((h) => statusByHandle.get(h)?.status === "verified"),
          ...correctionEntries.map(([, good]) => good),
        ].map((h) => [h.toLowerCase(), h]),
      ).values(),
    ].sort();
    if (buttonHandles.length > 0) {
      kb.row();
      buttonHandles.forEach((h, i) => {
        if (i > 0 && i % 4 === 0) kb.row();
        kb.url(`🔗 @${h}`, `https://x.com/${h}`);
      });
    }

    await ctx.reply(lines.join("\n"), {
      parse_mode: "Markdown",
      reply_markup: kb,
    });
  } catch (err) {
    console.error("[bot] tweet generation failed:", err);
    await ctx.reply(`❌ Error generando tweets: ${(err as Error).message}`);
  }
}

async function startFbFlow(published: PublishedEntry, state: PendingState, ctx: MyContext): Promise<void> {
  const xTweet = published.social.x?.tweet;
  const fbState = published.social.facebook ?? { status: "queued" };
  published.social.facebook = fbState;

  if (xTweet) {
    // Primary path: Facebook reuses the exact approved X tweet text (the X
    // rules — tense, RAE, style — apply by construction).
    fbState.status = "approved";
    fbState.tweet = xTweet;
    fbState.tweetProvider = published.social.x?.tweetProvider;
    await saveState(state);

    await showFbApproval(
      published,
      state,
      ctx,
      xTweet,
      published.caption ?? "",
      "📋 Usamos para Facebook el mismo texto aprobado para X:",
    );
    return;
  }

  // Fallback (no approved X tweet): dual-LLM FB text picker.
  await ctx.reply(`⏳ Generando propuestas de post para Facebook *"${published.title}"* (Gemini + DeepSeek)...`, {
    parse_mode: "Markdown",
  });

  try {
    const pendingEntry = state.pending.find((e) => e.id === published.id);
    const source = pendingEntry?.prepared ?? {
      title: published.title,
      description: "",
      content: "",
      tags: [],
      category: "",
      slug: published.slug,
      pubDate: published.publishedAt,
      basePath: "",
      media_url: "",
      igMediaId: published.id,
      mdxPath: "",
      imagePath: "",
    };

    const { generateFbTexts } = await import("./social/fbpost");
    const texts = await generateFbTexts(
      source as PreparedPost,
      published.caption ?? pendingEntry?.post.caption ?? "",
      published.postTimestamp ?? pendingEntry?.post.timestamp,
    );
    fbState.status = "queued";
    fbState.tweet = undefined;
    await saveState(state);

    const lines: string[] = ["Selecciona la versión del post para Facebook:"];
    if (texts.gemini) {
      lines.push("", `📝 *GEMINI:*\n${escMarkdown(texts.gemini)}`);
    }
    if (texts.deepseek) {
      lines.push("", `📝 *DEEPSEEK:*\n${escMarkdown(texts.deepseek)}`);
    }
    if (texts.gemini && texts.deepseek && texts.gemini === texts.deepseek) {
      lines.push("", "⚠️ Ambas versiones son idénticas.");
    }

    const kb = new InlineKeyboard();
    if (texts.gemini) kb.text("❤️ FB Gemini", `pickFb:gemini:${published.id}`);
    if (texts.deepseek) kb.text("💙 FB DeepSeek", `pickFb:deepseek:${published.id}`);

    await ctx.reply(lines.join("\n"), {
      parse_mode: "Markdown",
      reply_markup: kb,
    });
  } catch (err) {
    console.error("[bot] fb generation failed:", err);
    await ctx.reply(`❌ Error generando post de Facebook: ${(err as Error).message}`);
  }
}

/**
 * Show the Facebook approval state for the given text: grounded page
 * suggestions (✅ Usar), repair options and — when clean — the 📤 Publicar en
 * redes button right here in the last message (no scrolling back).
 */
async function showFbApproval(
  published: PublishedEntry,
  state: PendingState,
  ctx: MyContext,
  fbText: string,
  caption: string,
  header = "",
  statusMsg?: { chatId: number; messageId: number },
): Promise<void> {
  const postId = published.id;
  const fbState = published.social.facebook;
  const { extractHandles } = await import("./social/xverify");
  let suggestions: { handle: string; pageName: string; pageUrl?: string; source?: "web" | "gemini" }[] = [];
  try {
    const { suggestFbPages } = await import("./social/fbverify");
    suggestions = await suggestFbPages(fbText, caption);
  } catch (err) {
    console.warn("[bot] FB page suggestions failed:", (err as Error).message);
    // Fall through with no suggestions — the repair keyboard must still show.
  }
  ctx.session.fbSuggestions = { ...ctx.session.fbSuggestions, [postId]: suggestions };

  const { isValidFbPageUrl } = await import("./social/fbverify");
  const fbPageUrlFor = (s: { pageName: string; pageUrl?: string }): string =>
    s.pageUrl && isValidFbPageUrl(s.pageUrl)
      ? s.pageUrl
      : `https://www.facebook.com/${encodeURIComponent(s.pageName)}`;

  // AUTO-APPLY the verification: record each verified page per @handle in
  // state WITHOUT touching the text — the @mentions stay as-is so the user
  // can add the real tags in the Facebook editor later.
  const existingMentions = fbState?.fbMentions ?? [];
  const mentionsByHandle = new Map(existingMentions.map((m) => [m.handle.toLowerCase(), m]));
  let mentionsChanged = false;
  for (const s of suggestions) {
    const key = s.handle.toLowerCase();
    if (!mentionsByHandle.has(key) || mentionsByHandle.get(key)?.pageName !== s.pageName) {
      mentionsByHandle.set(key, { handle: s.handle, pageName: s.pageName, pageUrl: fbPageUrlFor(s) });
      mentionsChanged = true;
    }
  }
  if (mentionsChanged) {
    fbState!.fbMentions = [...mentionsByHandle.values()];
    await saveState(state);
  }
  const mentionMap = mentionsByHandle;

  const handlesInText = extractHandles(fbText);
  const unverified = handlesInText.filter((h) => !mentionMap.has(h.toLowerCase()));
  const allHandlesVerified = handlesInText.length === 0 || unverified.length === 0;
  const approvedWithHandles = fbState?.handlesApproved === true;

  const kb = new InlineKeyboard();
  kb.text("✏️ Editar texto", `editFb:${postId}`).text("✏️ Editar menciones", `editHandlesFb:${postId}`).text("❌ Rechazar", `rejectFb:${postId}`);
  // ✂️ Quitar menciones ALWAYS while mentions remain — with the verified-page
  // 🔗 buttons on the SAME row (deduplicated by URL, one per page).
  if (handlesInText.length > 0) {
    kb.row().text("✂️ Quitar menciones", `fixFb:${postId}`);
    const seenUrls = new Set<string>();
    for (const m of mentionMap.values()) {
      if (!m.pageUrl) continue;
      const url = fbPageUrlFor(m);
      if (seenUrls.has(url.toLowerCase())) continue;
      seenUrls.add(url.toLowerCase());
      const label = m.pageName.length > 25 ? `${m.pageName.slice(0, 25)}…` : m.pageName;
      kb.url(`🔗 ${label}`, url);
    }
    // Approve the text AS-IS (unverified @mentions stay — tags added in FB editor).
    if (unverified.length > 0 && !approvedWithHandles) {
      kb.row().text("✅ Aprobar", `approveFbHandles:${postId}`);
    }
  }
  // The publish trigger lives HERE (last message) — never scroll back.
  if (allHandlesVerified || approvedWithHandles) {
    kb.row().text("📤 Publicar en redes", `social:${postId}`);
  }

  // Render the text with every VERIFIED @handle clickable (link to its page).
  // Unverified @handles stay as plain text — no Facebook search links.
  const escapeRegex = (s: string): string => s.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  const linkKeys = new Map<string, string>(); // raw token -> url
  const verifiedLines: string[] = [];
  for (const m of mentionMap.values()) {
    if (!m.pageUrl) continue;
    const url = fbPageUrlFor(m);
    linkKeys.set(`@${m.handle}`, url);
    linkKeys.set(`@${m.handle.toLowerCase()}`, url);
    linkKeys.set(m.pageName, url);
    linkKeys.set(m.pageName.toLowerCase(), url);
    verifiedLines.push(`@${escMarkdown(m.handle)} → [${m.pageName}](${url})`);
  }
  let rendered: string;
  if (linkKeys.size === 0) {
    rendered = escMarkdown(fbText);
  } else {
    const linkRegex = new RegExp(
      `(${[...linkKeys.keys()].sort((a, b) => b.length - a.length).map(escapeRegex).join("|")})`,
      "g",
    );
    rendered = fbText
      .split(linkRegex)
      .map((part) => (linkKeys.has(part) ? `[${part}](${linkKeys.get(part)})` : escMarkdown(part)))
      .join("");
  }
  const verifiedNote = verifiedLines.length > 0 ? `\n\n✅ Menciones verificadas: ${verifiedLines.join(" · ")}` : "";
  const body = allHandlesVerified || approvedWithHandles
    ? `✅ *Texto de Facebook aprobado:*\n\n${rendered}${verifiedNote}${approvedWithHandles ? `\n\n*Nota:* las @menciones se mantienen — añade los tags reales en el editor de Facebook.` : ""}\n\n✅ Textos de X y Facebook aprobados.\n\nPulsa 📤 Publicar en redes para publicar.`
    : `📝 *Texto de Facebook:*\n\n${rendered}${verifiedNote}\n\n⚠️ Hay menciones sin página verificada: usa ✂️ Quitar menciones o ✅ Aprobar.`;
  const text = header ? `${header}\n\n${body}` : body;

  if (statusMsg) {
    await ctx.api.editMessageText(statusMsg.chatId, statusMsg.messageId, text, { parse_mode: "Markdown", reply_markup: kb });
  } else {
    await ctx.reply(text, { parse_mode: "Markdown", reply_markup: kb });
  }

  // Persist verified FB pages found during this approval.
  const { dumpKnownFbPages } = await import("./social/fbverify");
  const dumpedPages = dumpKnownFbPages();
  if (Object.keys(dumpedPages).length > 0) {
    state.knownFbPages = { ...state.knownFbPages, ...dumpedPages };
    await saveState(state);
  }
}

// ---- Publish helpers (server-side gates enforced here) ----

interface PublishResult {
  ok: boolean;
  line: string;
}

async function publishToX(published: PublishedEntry, state: PendingState): Promise<PublishResult> {
  const xState = published.social.x;
  if (!xState?.tweet) return { ok: false, line: "❌ *X:* no hay tweet aprobado" };
  if (xState.status === "published") return { ok: true, line: "✅ *X:* ya estaba publicado" };
  try {
    const { verifyTweetHandles } = await import("./social/xverify");
    const handleInfos = await verifyTweetHandles(xState.tweet);
    if (handleInfos.some((h) => h.status !== "verified")) {
      return { ok: false, line: "❌ *X:* menciones sin verificar — pulsa ▶️ Publicar en redes para arreglarlas." };
    }
    const { getXChannel, createPost } = await import("./social/buffer");
    const channel = await getXChannel();
    const imageUrl = getPostImageUrl(published.slug);
    const post = await createPost(channel.id, xState.tweet, imageUrl ?? undefined);
    xState.status = "published";
    xState.publishedAt = new Date().toISOString();
    xState.error = undefined;
    await saveState(state);
    const link = post.externalLink ?? `https://x.com/pegadacarteles`;
    await sendAlert(`[Urban Sync] Tweet publicado en X: ${published.title}`, `${xState.tweet}\n\n${link}`);
    return { ok: true, line: `✅ *X:* ${escMarkdown(link)}` };
  } catch (err) {
    console.error("[bot] postX failed:", err);
    xState.status = "failed";
    xState.error = (err as Error).message;
    await saveState(state);
    await sendAlert("[Urban Sync] Error publicando tweet en X", `${(err as Error).message}\n\nPost: ${published.title}`);
    return { ok: false, line: `❌ *X:* ${(err as Error).message}` };
  }
}

async function publishToFb(published: PublishedEntry, state: PendingState): Promise<PublishResult> {
  const fbState = published.social.facebook;
  if (!fbState?.tweet) return { ok: false, line: "❌ *Facebook:* no hay post aprobado" };
  if (fbState.status === "published") return { ok: true, line: "✅ *Facebook:* ya estaba publicado" };
  try {
    const { extractHandles } = await import("./social/xverify");
    // Gate: refuse @mentions that are neither verified (in fbMentions) nor
    // explicitly approved (✅ Aprobar).
    const handles = extractHandles(fbState.tweet);
    const mentions = fbState.fbMentions ?? [];
    const unverified = handles.filter((h) => !mentions.some((m) => m.handle.toLowerCase() === h.toLowerCase()));
    if (unverified.length > 0 && fbState.handlesApproved !== true) {
      return { ok: false, line: "❌ *Facebook:* menciones sin verificar — usa ✂️ Quitar menciones o ✅ Aprobar." };
    }
    const blogUrl = `https://urbanstylepublicity.com/blog/${published.slug}`;

    // Direct Facebook Graph API (own page token) → REAL mentions (message_tags).
    // Falls back to Buffer when no token is configured or the call fails.
    if (process.env.FB_ACCESS_TOKEN && process.env.FB_PAGE_ID) {
      try {
        const { createFbPost } = await import("./social/facebook");
        const post = await createFbPost(fbState.tweet, blogUrl);
        fbState.status = "published";
        fbState.publishedAt = new Date().toISOString();
        fbState.error = undefined;
        await saveState(state);
        await sendAlert(`[Urban Sync] Publicado en Facebook (Graph API): ${published.title}`, `${fbState.tweet}\n\n${post.externalLink}`);
        return { ok: true, line: `✅ *Facebook (directo):* ${escMarkdown(post.externalLink)}` };
      } catch (err) {
        // Fallback to Buffer below (no post was created on a failed call).
        console.warn("[bot] postFb direct FB failed — falling back to Buffer:", (err as Error).message);
      }
    }

    const { getFbChannel, createPost } = await import("./social/buffer");
    const channel = await getFbChannel();
    const imageUrl = getPostImageUrl(published.slug);
    const fbType = published.mediaType === "VIDEO" ? "reel" : "post";
    const post = await createPost(channel.id, fbState.tweet, imageUrl ?? undefined, fbType);
    fbState.status = "published";
    fbState.publishedAt = new Date().toISOString();
    fbState.error = undefined;
    await saveState(state);
    const link = post.externalLink ?? "";
    await sendAlert(`[Urban Sync] Publicado en Facebook: ${published.title}`, `${fbState.tweet}\n\n${link}`);
    return { ok: true, line: link ? `✅ *Facebook:* ${escMarkdown(link)}` : "✅ *Facebook:* publicado" };
  } catch (err) {
    console.error("[bot] postFb failed:", err);
    fbState.status = "failed";
    fbState.error = (err as Error).message;
    await saveState(state);
    await sendAlert("[Urban Sync] Error publicando en Facebook", `${(err as Error).message}\n\nPost: ${published.title}`);
    return { ok: false, line: `❌ *Facebook:* ${(err as Error).message}` };
  }
}

async function publishToGbp(published: PublishedEntry, state: PendingState): Promise<PublishResult> {
  const fbState = published.social.facebook;
  const gbpState = published.social.gbp ?? { status: "queued" };
  published.social.gbp = gbpState;
  if (!fbState?.tweet) return { ok: false, line: "❌ *Google:* no hay texto de Facebook aprobado" };
  if (gbpState.status === "published") return { ok: true, line: "✅ *Google:* ya estaba publicado" };
  try {
    const { stripMentions, createGbpPost } = await import("./social/gbp");
    const text = stripMentions(fbState.tweet, fbState.fbMentions ?? []);
    if (!text) return { ok: false, line: "❌ *Google:* texto vacío tras quitar menciones" };
    const imageUrl = getPostImageUrl(published.slug);
    const post = await createGbpPost(text, imageUrl ?? undefined);
    gbpState.status = "published";
    gbpState.tweet = text;
    gbpState.publishedAt = new Date().toISOString();
    gbpState.error = undefined;
    await saveState(state);
    const link = post.externalLink ?? "";
    await sendAlert(`[Urban Sync] Publicado en Google Business Profile: ${published.title}`, `${text}\n\n${link}`);
    return { ok: true, line: link ? `✅ *Google:* ${escMarkdown(link)}` : "✅ *Google:* publicado" };
  } catch (err) {
    console.error("[bot] postGbp failed:", err);
    gbpState.status = "failed";
    gbpState.error = (err as Error).message;
    await saveState(state);
    await sendAlert("[Urban Sync] Error publicando en Google Business Profile", `${(err as Error).message}\n\nPost: ${published.title}`);
    return { ok: false, line: `❌ *Google:* ${(err as Error).message}` };
  }
}

bot.callbackQuery(/^social:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  console.log(`[bot] social: tapped ${postId}`);
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  if (!published) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado en publicados" });
    return;
  }

  const xDone = published.social.x?.status === "published";
  const fbDone = published.social.facebook?.status === "published";
  const gbpDone = published.social.gbp?.status === "published";
  const xState = published.social.x;
  const fbState = published.social.facebook;

  await ctx.answerCallbackQuery({ text: "Preparando redes..." }).catch(() => {
    // Stale/expired callback query (e.g. double tap or bot restart) — the
    // handler must keep running, the answer is only a toast.
  });
  // Immediate visible feedback: the readiness checks (X handle scraping +
  // grounded lookups) can take a minute or two — never leave the tap silent.
  const statusMsg = await ctx.reply("⏳ Comprobando textos aprobados y menciones...", { parse_mode: "Markdown" });
  if (xDone && fbDone && gbpDone) {
    await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, "✅ Este post ya está publicado en X, Facebook y Google.", { parse_mode: "Markdown" });
    return;
  }

  // Readiness: "clean" = an approved/published text exists with all mentions
  // resolved, for platforms still pending. A fresh post (no state at all) is
  // NOT clean — it must go through the preparation flows first.
  let xClean = !!xState?.tweet && (xState.status === "approved" || xState.status === "published");
  let fbClean = !!fbState?.tweet && (fbState.status === "approved" || fbState.status === "published");
  if (xClean && !xDone && xState?.tweet) {
    const { verifyTweetHandles } = await import("./social/xverify");
    xClean = (await verifyTweetHandles(xState.tweet)).every((h) => h.status === "verified");
  }
  if (fbClean && !fbDone && fbState?.tweet) {
    const { extractHandles } = await import("./social/xverify");
    // Clean when no @mentions remain, OR every @handle has a verified page in
    // fbMentions, OR the user explicitly approved with ✅ Aprobar.
    const handles = extractHandles(fbState.tweet);
    const mentions = fbState.fbMentions ?? [];
    fbClean =
      handles.length === 0 ||
      handles.every((h) => mentions.some((m) => m.handle.toLowerCase() === h.toLowerCase())) ||
      fbState.handlesApproved === true;
  }

  if (xClean && fbClean) {
    // Everything approved → publish the remaining platforms. GBP is derived
    // from the approved FB text (mentions stripped), so fbClean implies GBP
    // is ready — no separate GBP approval step.
    const statusMsg = await ctx.reply("⏳ Publicando en redes...", { parse_mode: "Markdown" });
    const lines: string[] = [];
    if (!xDone) {
      await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, "⏳ Publicando en X (@pegadacarteles) vía Buffer...", { parse_mode: "Markdown" });
      const rx = await publishToX(published, state);
      lines.push(rx.line);
      if (!rx.ok) {
        await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, lines.join("\n"), { parse_mode: "Markdown" });
        return;
      }
    }
    if (!fbDone) {
      await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, "⏳ Publicando en Facebook (Urban Style Publicity) vía Buffer...", { parse_mode: "Markdown" });
      const rf = await publishToFb(published, state);
      lines.push(rf.line);
      if (!rf.ok) {
        await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, lines.join("\n"), { parse_mode: "Markdown" });
        return;
      }
    }
    if (!gbpDone) {
      await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, "⏳ Publicando en Google Business Profile (Urban Style Publicity) vía Buffer...", { parse_mode: "Markdown" });
      const rg = await publishToGbp(published, state);
      lines.push(rg.line);
      if (!rg.ok) {
        await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, lines.join("\n"), { parse_mode: "Markdown" });
        return;
      }
    }
    await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, `✅ *Publicado en redes:*\n\n${lines.join("\n")}`, { parse_mode: "Markdown" });
    return;
  }

  // Not ready yet → prepare the pending platform (X first).
  if (!xDone && !xClean) {
    published.social.x = { status: "queued" };
    await saveState(state);
    await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, "⏳ Preparando texto de X (Gemini + DeepSeek)...", { parse_mode: "Markdown" });
    await startTweetFlow(published, state, ctx);
  } else if (!fbDone && !fbClean) {
    published.social.facebook = { status: "queued" };
    await saveState(state);
    await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, "⏳ Preparando texto de Facebook...", { parse_mode: "Markdown" });
    await startFbFlow(published, state, ctx);
  } else {
    await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, "✅ Textos listos. Pulsa 📤 Publicar en redes para publicar.", {
      parse_mode: "Markdown",
      reply_markup: new InlineKeyboard().text("📤 Publicar en redes", `social:${postId}`),
    });
  }
});

bot.callbackQuery(/^pickTweet:(gemini|deepseek):(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const provider = ctx.match[1] as "gemini" | "deepseek";
  const postId = ctx.match[2];
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  if (!published) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado en publicados" });
    return;
  }
  const xState = published.social.x ?? { status: "queued" };
  await ctx.answerCallbackQuery({ text: `Tweet de ${provider} elegido` });

  // Use the EXACT version the user picked (stored by startTweetFlow) — no
  // regeneration, so mentions like @movistararenaes are never dropped.
  // Falls back to regeneration only if the session was lost (bot restart).
  const pendingEntry = state.pending.find((e) => e.id === postId);
  const source = pendingEntry?.prepared ?? {
    title: published.title,
    description: "",
    content: "",
    tags: [],
    category: "",
    slug: published.slug,
    pubDate: published.publishedAt,
    basePath: "",
    media_url: "",
    igMediaId: postId,
    mdxPath: "",
    imagePath: "",
  };

  const statusMsg = await ctx.reply(`⏳ Preparando el tweet elegido con ${provider}...`);
  try {
    const candidates = ctx.session.tweetCandidates?.[postId];
    let tweet: string;
    const picked = candidates?.[provider];
    if (picked) {
      tweet = picked;
    } else {
      const prompt = buildTweetPrompt(source as PreparedPost, published.caption ?? "", published.postTimestamp ?? "");
      const text = await generateTextCompletion(prompt, provider, { temperature: 0.8, maxTokens: 8000 });
      const body = text
        .trim()
        .replace(/^["']|["']$/g, "")
        .replace(/https?:\/\/\S+/g, "")
        .replace(/\s+/g, " ")
        .trim();
      const url = `https://urbanstylepublicity.com/blog/${published.slug}`;
      const maxBody = 280 - 23; // URL counts as 23 chars on X (t.co)
      const trimmedBody = body.length > maxBody ? body.slice(0, maxBody) : body;
      tweet = `${trimmedBody} ${url}`.trim();
    }

    xState.status = "approved";
    xState.tweet = tweet;
    xState.tweetProvider = provider;
    published.social.x = xState;
    await saveState(state);

    // Verify @handles in the final tweet; for invalid/suspicious handles,
    // look up the official handle (web search first, grounded Gemini as
    // fallback) and AUTO-APPLY the correction — no manual "Usar" button.
    const { verifyTweetHandles, formatHandleStatus, extractHandles, findOfficialHandle } = await import("./social/xverify");
    let finalTweet = tweet;
    let handleInfos = await verifyTweetHandles(finalTweet);
    let hasInvalid = handleInfos.some((h) => h.status !== "verified");
    const statusLines = handleInfos.map((h) => formatHandleStatus(h));
    const suggestionLines: string[] = []; // pre-escaped, raw *bold* markers

    if (hasInvalid && extractHandles(finalTweet).length > 0) {
      await ctx.api.editMessageText(
        ctx.chat!.id,
        statusMsg.message_id,
        `⏳ Buscando cuentas oficiales en X (Google)...`,
      );
      const caption = published.caption ?? "";
      for (const info of handleInfos) {
        if (info.status !== "verified") {
          const official = await findOfficialHandle(info.handle, caption);
          if (official?.status === "verified") {
            finalTweet = finalTweet.replace(new RegExp(`@${info.handle}`, "gi"), `@${official.handle}`);
            suggestionLines.push(
              `✅ Menciones corregidas automáticamente: @${escMarkdown(info.handle)} → *@${escMarkdown(official.handle)}*`,
            );
          }
        }
      }
      if (suggestionLines.length > 0) {
        xState.tweet = finalTweet;
        await saveState(state);
        // Re-verify after the automatic corrections.
        handleInfos = await verifyTweetHandles(finalTweet);
        statusLines.length = 0;
        statusLines.push(...handleInfos.map((h) => formatHandleStatus(h)));
        hasInvalid = handleInfos.some((h) => h.status !== "verified");
      }
    }

    const canPublish = handleInfos.every((h) => h.status === "verified");
    // Only verified handles are clickable in the final text.
    const verifiedSet = new Set(handleInfos.filter((h) => h.status === "verified").map((h) => h.handle.toLowerCase()));
    const kb = new InlineKeyboard();
    kb.text("✏️ Editar tweet", `editTweet:${postId}`).text("✏️ Editar menciones", `editHandlesX:${postId}`).text("❌ Rechazar", `rejectTweet:${postId}`);
    if (hasInvalid && extractHandles(finalTweet).length > 0) {
      kb.row().text("✂️ Auto-arreglar", `fixTweet:${postId}`);
    }
    // Clickable profile links ONLY for accounts that exist (verified),
    // deduplicated case-insensitively.
    const verifiedHandles = [
      ...new Map(handleInfos.filter((h) => h.status === "verified").map((h) => [h.handle.toLowerCase(), h.handle])).values(),
    ];
    if (verifiedHandles.length > 0) {
      kb.row();
      verifiedHandles.forEach((h, i) => {
        if (i > 0 && i % 4 === 0) kb.row();
        kb.url(`🔗 @${h}`, `https://x.com/${h}`);
      });
    }

    await ctx.api.editMessageText(
      ctx.chat!.id,
      statusMsg.message_id,
      `✅ Tweet listo (${finalTweet.length} caracteres):\n\n${linkifyHandles(finalTweet, verifiedSet)}${statusLines.length ? `\n\n${statusLines.map((s) => escMarkdown(s)).join("\n")}` : ""}${suggestionLines.length ? `\n\n${suggestionLines.join("\n")}` : ""}${canPublish ? "\n\n✅ Tweet aprobado — preparando Facebook..." : `\n\n⚠️ Resuelve las menciones antes de publicar.`}`,
      {
        parse_mode: "Markdown",
        reply_markup: kb,
      },
    );
    // Auto-advance to the Facebook flow once the tweet is clean.
    if (canPublish && published.social.facebook?.status !== "published") {
      try {
        await startFbFlow(published, state, ctx);
      } catch (err) {
        console.error("[bot] fb continuation failed:", err);
      }
    }
  } catch (err) {
    console.error("[bot] tweet regeneration failed:", err);
    await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, `❌ Error: ${(err as Error).message}`);
  }
});

// ---- Use official handle: replace @bad with @good in the approved tweet ----

bot.callbackQuery(/^useHandle:(.+):(.+):(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const bad = ctx.match[2];
  const good = ctx.match[3];
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  if (!published) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado en publicados" });
    return;
  }
  const xState = published.social.x;
  if (!xState?.tweet) {
    await ctx.answerCallbackQuery({ text: "No hay tweet aprobado" });
    return;
  }

  const replaced = xState.tweet.replace(new RegExp(`@${bad}`, "gi"), `@${good}`);
  xState.tweet = replaced;
  await saveState(state);

  const { verifyTweetHandles, formatHandleStatus, extractHandles } = await import("./social/xverify");
  const infos = await verifyTweetHandles(replaced);
  const canPublish = infos.every((h) => h.status === "verified");
  const kb = new InlineKeyboard();
  kb.text("✏️ Editar tweet", `editTweet:${postId}`);
  kb.row().text("❌ Rechazar", `rejectTweet:${postId}`);
  if (!canPublish && extractHandles(replaced).length > 0) {
    kb.row().text("✂️ Auto-arreglar", `fixTweet:${postId}`);
  }
  kb.row().url(`🔗 Ver @${good}`, `https://x.com/${good}`);

  await ctx.answerCallbackQuery({ text: `@${bad} → @${good}` });
  await ctx.reply(
    `✅ Menciones corregidas: @${escMarkdown(bad)} → @${escMarkdown(good)}\n\n${escMarkdown(replaced)}${!canPublish && extractHandles(replaced).length > 0 ? `\n\n⚠️ Resuelve las menciones antes de publicar.` : ""}${infos.length > 0 ? `\n\n${infos.map((i) => escMarkdown(formatHandleStatus(i))).join("\n")}` : ""}${canPublish ? `\n\n✅ Tweet aprobado — preparando Facebook...` : ""}`,
    {
      parse_mode: "Markdown",
      reply_markup: kb,
    },
  );
  // Auto-advance to the Facebook flow once the tweet is clean.
  if (canPublish && published.social.facebook?.status !== "published") {
    try {
      await startFbFlow(published, state, ctx);
    } catch (err) {
      console.error("[bot] fb continuation failed:", err);
    }
  }
});

// ---- Auto-fix invalid @handles: strip @ + regenerate without the mention ----

bot.callbackQuery(/^fixTweet:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  if (!published) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado en publicados" });
    return;
  }
  const xState = published.social.x;
  if (!xState?.tweet) {
    await ctx.answerCallbackQuery({ text: "No hay tweet aprobado" });
    return;
  }

  const { extractHandles, verifyTweetHandles, formatHandleStatus } = await import("./social/xverify");
  const invalidHandles = (await verifyTweetHandles(xState.tweet))
    .filter((h) => h.status === "invalid")
    .map((h) => h.handle);

  await ctx.answerCallbackQuery({ text: "Quitando menciones inválidas..." });
  const statusMsg = await ctx.reply(`⏳ Regenerando sin las menciones inválidas: ${invalidHandles.map((h) => `@${h}`).join(", ")}...`);

  try {
    const pendingEntry = state.pending.find((e) => e.id === postId);
    const source = pendingEntry?.prepared ?? {
      title: published.title,
      description: "",
      content: "",
      tags: [],
      category: "",
      slug: published.slug,
      pubDate: published.publishedAt,
      basePath: "",
      media_url: "",
      igMediaId: postId,
      mdxPath: "",
      imagePath: "",
    };

    const { buildTweetPrompt } = await import("./tweet");
    const invalidList = invalidHandles.map((h) => `@${h}`).join(", ");
    const feedback = `The tweet mentions ${invalidList}, which do not exist on X. Regenerate the tweet using the plain name (without @) for ${invalidList}. Keep everything else the same.`;
    const prompt = `${buildTweetPrompt(source as PreparedPost, published.caption ?? "", published.postTimestamp ?? "")}\n\n${feedback}`;

    const text = await generateTextCompletion(prompt, xState.tweetProvider ?? "gemini", { temperature: 0.8, maxTokens: 8000 });
    const body = text
      .trim()
      .replace(/^["']|["']$/g, "")
      .replace(/https?:\/\/\S+/g, "")
      .replace(/\s+/g, " ")
      .trim();
    const url = `https://urbanstylepublicity.com/blog/${published.slug}`;
    const maxBody = 280 - 23;
    const trimmedBody = body.length > maxBody ? body.slice(0, maxBody) : body;
    const newTweet = `${trimmedBody} ${url}`.trim();

    xState.tweet = newTweet;
    await saveState(state);

    // Re-verify after fix
    const newInfos = await verifyTweetHandles(newTweet);
    const statusLines = newInfos.map((h) => formatHandleStatus(h));
    const canPublish = newInfos.every((h) => h.status === "verified");
    const kb = new InlineKeyboard();
    kb.text("✏️ Editar tweet", `editTweet:${postId}`)
      .row()
      .text("❌ Rechazar", `rejectTweet:${postId}`);
    if (newInfos.some((h) => h.status !== "verified") && extractHandles(newTweet).length > 0) {
      kb.row().text("✂️ Auto-arreglar", `fixTweet:${postId}`);
    }

    await ctx.api.editMessageText(
      ctx.chat!.id,
      statusMsg.message_id,
      `✂️ Tweet regenerado sin menciones inválidas (${newTweet.length} caracteres):\n\n${escMarkdown(newTweet)}${statusLines.length ? `\n\n${statusLines.map((s) => escMarkdown(s)).join("\n")}` : ""}${!canPublish && extractHandles(newTweet).length > 0 ? `\n\n⚠️ Resuelve las menciones antes de publicar.` : `\n\n✅ Tweet aprobado — preparando Facebook...`}`,
      {
        parse_mode: "Markdown",
        reply_markup: kb,
      },
    );
    // Auto-advance to the Facebook flow once the tweet is clean.
    if (canPublish && published.social.facebook?.status !== "published") {
      try {
        await startFbFlow(published, state, ctx);
      } catch (err) {
        console.error("[bot] fb continuation failed:", err);
      }
    }
  } catch (err) {
    console.error("[bot] fixTweet failed:", err);
    await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, `❌ Error: ${(err as Error).message}`);
  }
});

// ---- Pick FB post version, regenerate final text, suggest FB pages ----

bot.callbackQuery(/^pickFb:(gemini|deepseek):(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const provider = ctx.match[1] as "gemini" | "deepseek";
  const postId = ctx.match[2];
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  if (!published) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado en publicados" });
    return;
  }
  const fbState = published.social.facebook ?? { status: "queued" };
  await ctx.answerCallbackQuery({ text: `Post de ${provider} elegido` });

  const pendingEntry = state.pending.find((e) => e.id === postId);
  const source = pendingEntry?.prepared ?? {
    title: published.title,
    description: "",
    content: "",
    tags: [],
    category: "",
    slug: published.slug,
    pubDate: published.publishedAt,
    basePath: "",
    media_url: "",
    igMediaId: postId,
    mdxPath: "",
    imagePath: "",
  };

  const statusMsg = await ctx.reply(`⏳ Generando post definitivo de Facebook con ${provider}...`);
  try {
    const { buildFbPrompt } = await import("./social/fbpost");
    const prompt = buildFbPrompt(source as PreparedPost, published.caption ?? "", published.postTimestamp ?? "");
    const text = await generateTextCompletion(prompt, provider, { temperature: 0.8, maxTokens: 8000 });
    let body = text
      .trim()
      .replace(/^["']|["']$/g, "")
      .replace(/https?:\/\/\S+/g, "")
      .replace(/\s+/g, " ")
      .trim();
    if (body.length > 1000) {
      // Cap at a word boundary, mirroring editFb's limit
      body = body.slice(0, 1000);
      const lastSpace = body.lastIndexOf(" ");
      if (lastSpace > 60) body = body.slice(0, lastSpace);
    }
    const url = `https://urbanstylepublicity.com/blog/${published.slug}`;
    const fbText = `${body} ${url}`.trim();

    fbState.status = "approved";
    fbState.tweet = fbText;
    fbState.tweetProvider = provider;
    published.social.facebook = fbState;
    await saveState(state);

    await showFbApproval(published, state, ctx, fbText, published.caption ?? pendingEntry?.post.caption ?? "", "", { chatId: ctx.chat!.id, messageId: statusMsg.message_id });
  } catch (err) {
    console.error("[bot] fb regeneration failed:", err);
    await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, `❌ Error: ${(err as Error).message}`);
  }
});

// ---- Use suggested FB page: replace @handle with the page name ----

bot.callbackQuery(/^useFbPage:(.+):(\d+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const index = Number(ctx.match[2]);
  const suggestions = ctx.session.fbSuggestions?.[postId] ?? [];
  const suggestion = suggestions[index];
  if (!suggestion) {
    await ctx.answerCallbackQuery({ text: "Sugerencia expirada — regenera el post" });
    return;
  }

  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  const fbState = published?.social.facebook;
  if (!published || !fbState?.tweet) {
    await ctx.answerCallbackQuery({ text: "No hay post de Facebook aprobado" });
    return;
  }

  const replaced = fbState.tweet.replace(new RegExp(`@${suggestion.handle}`, "gi"), () => suggestion.pageName);
  fbState.tweet = replaced;
  await saveState(state);

  const ackText = `@${suggestion.handle} → ${suggestion.pageName}`;
  await ctx.answerCallbackQuery({ text: ackText.slice(0, 199) });

  await showFbApproval(
    published,
    state,
    ctx,
    replaced,
    published.caption ?? "",
    `✅ Mención corregida: @${escMarkdown(suggestion.handle)} → *${escMarkdown(suggestion.pageName)}*`,
  );
});

// ---- Auto-fix @mentions in FB post: regenerate with plain names ----

bot.callbackQuery(/^fixFb:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  const fbState = published?.social.facebook;
  if (!published || !fbState?.tweet) {
    await ctx.answerCallbackQuery({ text: "No hay post de Facebook aprobado" });
    return;
  }

  await ctx.answerCallbackQuery({ text: "Quitando menciones..." });
  const statusMsg = await ctx.reply(`⏳ Regenerando sin menciones...`);

  try {
    const pendingEntry = state.pending.find((e) => e.id === postId);
    const source = pendingEntry?.prepared ?? {
      title: published.title,
      description: "",
      content: "",
      tags: [],
      category: "",
      slug: published.slug,
      pubDate: published.publishedAt,
      basePath: "",
      media_url: "",
      igMediaId: postId,
      mdxPath: "",
      imagePath: "",
    };

    const { buildTweetPrompt } = await import("./tweet");
    const feedback = "Do not use any @mentions — write the plain name of each artist/brand instead. Keep everything else the same.";
    const prompt = `${buildTweetPrompt(source as PreparedPost, published.caption ?? "", published.postTimestamp ?? "")}\n\n${feedback}`;

    const provider = fbState.tweetProvider ?? "gemini";
    const text = await generateTextCompletion(prompt, provider, { temperature: 0.8, maxTokens: 8000 });
    const body = text
      .trim()
      .replace(/^["']|["']$/g, "")
      .replace(/https?:\/\/\S+/g, "")
      .replace(/\s+/g, " ")
      .trim();
    const url = `https://urbanstylepublicity.com/blog/${published.slug}`;
    const maxBody = 280 - 23;
    const trimmedBody = body.length > maxBody ? body.slice(0, maxBody) : body;
    fbState.tweet = `${trimmedBody} ${url}`.trim();
    await saveState(state);

    await showFbApproval(published, state, ctx, fbState.tweet, published.caption ?? "", "✂️ Post regenerado sin menciones", { chatId: ctx.chat!.id, messageId: statusMsg.message_id });
  } catch (err) {
    console.error("[bot] fixFb failed:", err);
    await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, `❌ Error: ${(err as Error).message}`);
  }
});

// ---- Publish tweet to X via Buffer API ----

/**
 * Extract the blog post's header image URL (og:image) from the built page so
 * Buffer can attach the same IG image that's live on the site.
 */
function getPostImageUrl(slug: string): string | null {
  const { readFileSync, existsSync } = require("node:fs") as typeof import("node:fs");
  const { join } = require("node:path") as typeof import("node:path");
  const distPath = join(import.meta.dirname, "..", "..", "dist", "blog", slug, "index.html");
  if (!existsSync(distPath)) return null;
  const html = readFileSync(distPath, "utf8");

  // 1) og:image meta tag (absolute URL)
  const og = html.match(/<meta property="og:image" content="([^"]+)"/);
  if (og?.[1]) return og[1];

  // 2) Fallback: the fixed header img src + domain prefix
  const img = html.match(/<img[^>]*class="fixed top-0[^>]*src="([^"]+)"/);
  if (img?.[1]) return img[1].startsWith("http") ? img[1] : `https://urbanstylepublicity.com${img[1]}`;

  return null;
}

bot.callbackQuery(/^postX:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  if (!published) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado en publicados" });
    return;
  }
  const xState = published.social.x;
  if (!xState?.tweet) {
    await ctx.answerCallbackQuery({ text: "No hay tweet aprobado" });
    return;
  }
  if (xState.status === "published") {
    await ctx.answerCallbackQuery({ text: "Ya publicado en X" });
    return;
  }

  await ctx.answerCallbackQuery({ text: "Publicando en X..." });
  const statusMsg = await ctx.reply(`⏳ Publicando tweet en X (@pegadacarteles) vía Buffer...`, { parse_mode: "Markdown" });
  const rx = await publishToX(published, state);
  await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, rx.line, { parse_mode: "Markdown" });
});

bot.callbackQuery(/^postFb:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  if (!published) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado en publicados" });
    return;
  }
  const fbState = published.social.facebook;
  if (!fbState?.tweet) {
    await ctx.answerCallbackQuery({ text: "No hay post de Facebook aprobado" });
    return;
  }
  if (fbState.status === "published") {
    await ctx.answerCallbackQuery({ text: "Ya publicado en Facebook" });
    return;
  }

  await ctx.answerCallbackQuery({ text: "Publicando en Facebook..." });
  const statusMsg = await ctx.reply(`⏳ Publicando en Facebook (Urban Style Publicity) vía Buffer...`, { parse_mode: "Markdown" });
  const rf = await publishToFb(published, state);
  await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, rf.line, { parse_mode: "Markdown" });
});

bot.callbackQuery(/^editTweet:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  await ctx.answerCallbackQuery({ text: "Envía el nuevo tweet..." });
  await ctx.reply("✏️ Envía el texto del tweet (máx 280 caracteres):");
  ctx.session.awaitingEditFor = { postId, field: "tweet" };
});

// ---- Manual mention replacement (fallback when verification fails) ----

bot.callbackQuery(/^editHandlesX:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  await ctx.answerCallbackQuery({ text: "Envía la sustitución..." });
  await ctx.reply("✏️ Envía la sustitución de mención (ej. `@Anuel_2A → @Anuel_2bleA`):", { parse_mode: "Markdown" });
  ctx.session.awaitingEditFor = { postId, field: "xhandles" };
});

bot.callbackQuery(/^editHandlesFb:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  await ctx.answerCallbackQuery({ text: "Envía la sustitución..." });
  await ctx.reply("✏️ Envía la sustitución de mención (ej. `@movistararenaes → @movistararena`):", { parse_mode: "Markdown" });
  ctx.session.awaitingEditFor = { postId, field: "fbhandles" };
});

// ---- Approve the FB text AS-IS (the @mentions stay for the FB editor) ----

bot.callbackQuery(/^approveFbHandles:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  const fbState = published?.social.facebook;
  if (!published || !fbState?.tweet) {
    await ctx.answerCallbackQuery({ text: "No hay post de Facebook aprobado" });
    return;
  }
  fbState.handlesApproved = true;
  await saveState(state);
  await ctx.answerCallbackQuery({ text: "Texto aprobado con menciones" });
  await showFbApproval(
    published,
    state,
    ctx,
    fbState.tweet,
    published.caption ?? "",
    "✅ Aprobado — las @menciones se mantienen; añade los tags reales en el editor de Facebook.",
  );
});

bot.callbackQuery(/^rejectTweet:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  if (!published) return;
  published.social.x = { status: "queued" };
  await saveState(state);
  await ctx.answerCallbackQuery({ text: "Tweet rechazado" });
  await ctx.reply("❌ Tweet rechazado. Usa ▶️ Publicar en redes para regenerar.");
});

bot.callbackQuery(/^editFb:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  await ctx.answerCallbackQuery({ text: "Envía el nuevo texto..." });
  await ctx.reply("✏️ Envía el texto del post de Facebook:");
  ctx.session.awaitingEditFor = { postId, field: "fbtext" };
});

bot.callbackQuery(/^rejectFb:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  if (!published) return;
  published.social.facebook = { status: "queued" };
  await saveState(state);
  await ctx.answerCallbackQuery({ text: "Post rechazado" });
  await ctx.reply("❌ Post de Facebook rechazado. Usa ▶️ Publicar en redes para regenerar.");
});

bot.command("eliminar", async (ctx) => {
  if (!isAllowed(ctx)) return;
  const slug = ctx.match?.trim();
  if (!slug) {
    await ctx.reply("Uso: /eliminar <slug>");
    return;
  }
  const state = await freshState();
  const published = state.published.find((e) => e.slug === slug);
  if (!published) {
    await ctx.reply(`No se encontró el post publicado con slug "${slug}".`);
    return;
  }
  await removePublishedPost(published.id, ctx);
});

// ---- Commands ----

bot.command("start", async (ctx) => {
  if (!isAllowed(ctx)) return;
  await ctx.reply("Bot de aprobación de posts Urban Style activo. Envíame /help para ver los comandos.");
});

bot.command("help", async (ctx) => {
  if (!isAllowed(ctx)) return;
  await ctx.reply([
    "Comandos:",
    "/aprobar — aprobar el siguiente post pendiente",
    "/rechazar <feedback> — rechazar con instrucciones de cambio",
    "/estado — ver posts pendientes",
    "/instrucciones — listar instrucciones guardadas",
    "/instruccion add <texto> — añadir instrucción",
    "/instruccion remove <id> — eliminar instrucción",
  ].join("\n"));
});

bot.command("estado", async (ctx) => {
  if (!isAllowed(ctx)) return;
  const state = await freshState();
  if (state.pending.length === 0) {
    await ctx.reply("No hay posts pendientes.");
    return;
  }
  const lines = state.pending.map((e, i) => `${i + 1}. ${e.prepared.title} (${e.id})`).join("\n");
  await ctx.reply(`Posts pendientes (${state.pending.length}):\n${lines}`);
});

bot.command("instrucciones", async (ctx) => {
  if (!isAllowed(ctx)) return;
  const instructions = await loadInstructions();
  if (instructions.length === 0) {
    await ctx.reply("No hay instrucciones guardadas.");
    return;
  }
  await ctx.reply(`Instrucciones (${instructions.length}):\n${instructions.map((i) => `${i.id}: ${i.text}`).join("\n")}`);
});

bot.command("instruccion", async (ctx) => {
  if (!isAllowed(ctx)) return;
  const args = ctx.match?.trim();
  if (!args) {
    await ctx.reply("Uso: /instruccion add <texto> | /instruccion remove <id>");
    return;
  }

  if (args.startsWith("add ")) {
    const text = args.slice(4).trim();
    if (!text) return ctx.reply("Escribe el texto de la instrucción.");
    const instruction = await addInstruction(text, "manual");
    await ctx.reply(`✅ Instrucción guardada: ${instruction.id}\n"${instruction.text}"`);
  } else if (args.startsWith("remove ")) {
    const id = args.slice(7).trim();
    const removed = await removeInstruction(id);
    await ctx.reply(removed ? `✅ Instrucción ${id} eliminada.` : `❌ No se encontró ${id}.`);
  } else {
    await ctx.reply("Uso: /instruccion add <texto> | /instruccion remove <id>");
  }
});

// ---- Dual LLM picker ----

bot.callbackQuery(/^pick:(gemini|deepseek):(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const provider = ctx.match[1] as "gemini" | "deepseek";
  const postId = ctx.match[2];
  const state = await freshState();
  const entry = state.pending.find((e) => e.id === postId);
  if (!entry) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado o ya procesado" });
    return;
  }
  const chosen = entry.articles?.[provider];
  if (!chosen) {
    await ctx.answerCallbackQuery({ text: `La versión ${provider} no está disponible` });
    return;
  }
  await ctx.answerCallbackQuery({ text: `Elegida versión ${provider}` });
  entry.chosenProvider = provider;
  entry.chosenTitleProvider = provider;
  entry.chosenContentProvider = provider;
  entry.article = chosen;
  entry.prepared = preparePost(chosen, entry.post);
  await saveState(state);
  await ctx.reply(`❤️ Versión elegida: *${provider}*. Ahora puedes aprobar, rechazar o ajustar la imagen.`, {
    parse_mode: "Markdown",
  });
  await notifyTelegram("approval", entry, ctx.chat?.id ?? state.chatId);
});

// ---- Cross-pick: title from one provider, content from the other ----

bot.callbackQuery(/^mix:(gemini|deepseek):(gemini|deepseek):(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const titleProvider = ctx.match[1] as "gemini" | "deepseek";
  const contentProvider = ctx.match[2] as "gemini" | "deepseek";
  const postId = ctx.match[3];
  const state = await freshState();
  const entry = state.pending.find((e) => e.id === postId);
  if (!entry) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado o ya procesado" });
    return;
  }
  const titleArticle = entry.articles?.[titleProvider];
  const contentArticle = entry.articles?.[contentProvider];
  if (!titleArticle || !contentArticle) {
    await ctx.answerCallbackQuery({ text: "Combinación no disponible" });
    return;
  }
  await ctx.answerCallbackQuery({ text: `Título de ${titleProvider} + contenido de ${contentProvider}` });

  const mixed = {
    title: titleArticle.title,
    description: contentArticle.description,
    content: contentArticle.content,
    tags: contentArticle.tags,
    category: contentArticle.category,
  };
  entry.chosenTitleProvider = titleProvider;
  entry.chosenContentProvider = contentProvider;
  entry.chosenProvider = undefined; // mixed article — regeneration regenerates both
  entry.article = mixed;
  entry.prepared = preparePost(mixed, entry.post);
  await saveState(state);
  await ctx.reply(`🧩 Combinación elegida: título de *${titleProvider}* + contenido de *${contentProvider}*. Ahora puedes aprobar, rechazar o ajustar la imagen.`, {
    parse_mode: "Markdown",
  });
  await notifyTelegram("approval", entry, ctx.chat?.id ?? state.chatId);
});

// ---- Approval from inline buttons ----

bot.callbackQuery(/^approve:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const state = await freshState();
  const entry = state.pending.find((e) => e.id === postId);
  if (!entry) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado o ya procesado" });
    return;
  }
  await ctx.answerCallbackQuery({ text: "Aprobando..." });
  await publishEntry(entry, state, ctx);
});

bot.callbackQuery(/^reject:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const state = await freshState();
  const entry = state.pending.find((e) => e.id === postId);
  if (!entry) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado o ya procesado" });
    return;
  }
  await ctx.answerCallbackQuery({ text: "Esperando feedback..." });
  await ctx.reply(`Describe los cambios necesarios para el post "${entry.prepared.title}":`);
  ctx.session.awaitingFeedbackFor = postId;
});

bot.callbackQuery(/^preview:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const state = await freshState();
  const entry = state.pending.find((e) => e.id === postId);
  if (!entry) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado" });
    return;
  }
  await ctx.answerCallbackQuery();
  // Collapsible preview: the whole content in ONE expandable HTML blockquote
  // (Markdown rendered to HTML inside), trimmed near the end if over the limit.
  let contentHtml = mdToHtml(entry.prepared.content);
  const titleHtml = escHtml(entry.prepared.title);
  const headerHtml = `📖 <b>Contenido completo</b> (${titleHtml}):\n\n`;
  const budget = 4000 - headerHtml.length;
  if (contentHtml.length > budget) {
    const trimmed = contentHtml.slice(0, budget);
    const lastSpace = trimmed.lastIndexOf(" ");
    contentHtml = lastSpace > 80 ? trimmed.slice(0, lastSpace) : trimmed;
  }
  await ctx.reply(`${headerHtml}<blockquote expandable>${contentHtml}</blockquote>`, {
    parse_mode: "HTML",
  });
});

bot.callbackQuery(/^crop:(top|center|bottom):(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const position = ctx.match[1] as "top" | "center" | "bottom";
  const postId = ctx.match[2];
  const state = await freshState();
  const entry = state.pending.find((e) => e.id === postId);
  if (!entry) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado o ya procesado" });
    return;
  }
  entry.prepared.objectPosition = position;
  await saveState(state);
  await ctx.answerCallbackQuery({ text: `Posición imagen: ${position}` });
  await ctx.reply(`📐 Posición de imagen cambiada a *${position}*. Usa los botones para aprobar o ajustar.`, {
    parse_mode: "Markdown",
  });
});

// ---- Manual edits (title / description / content) ----

bot.callbackQuery(/^edit:(title|desc|content):(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const field = ctx.match[1] as "title" | "desc" | "content";
  const postId = ctx.match[2];
  const state = await freshState();
  const entry = state.pending.find((e) => e.id === postId);
  if (!entry) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado o ya procesado" });
    return;
  }
  await ctx.answerCallbackQuery({ text: "Esperando nuevo texto..." });
  const fieldLabel = { title: "título", desc: "descripción", content: "contenido" }[field];
  await ctx.reply(`✏️ Envía el nuevo *${fieldLabel}* para el post "${entry.prepared.title}":`, {
    parse_mode: "Markdown",
  });
  ctx.session.awaitingEditFor = { postId, field };
});

// ---- Text feedback for reject -> regenerate ----

bot.on("message:text", async (ctx) => {
  if (!isAllowed(ctx)) return;

  // 1) Manual edit in progress (✏️ buttons)
  const editFor = ctx.session.awaitingEditFor;
  if (editFor) {
    const newText = ctx.message.text.trim();
    ctx.session.awaitingEditFor = undefined;

    if (!newText) {
      await ctx.reply("El texto no puede estar vacío. El valor anterior se mantiene.");
      return;
    }

    // Tweet edit (published post social flow)
    if (editFor.field === "tweet") {
      if (newText.length > 280) {
        await ctx.reply(`⚠️ El tweet tiene ${newText.length} caracteres (máx 280). Envíalo de nuevo más corto.`);
        ctx.session.awaitingEditFor = editFor; // keep waiting
        return;
      }
      const state = await freshState();
      const published = state.published.find((e) => e.id === editFor.postId);
      if (!published) {
        await ctx.reply("Post no encontrado en publicados.");
        return;
      }
      published.social.x = {
        status: "approved",
        tweet: newText,
        tweetProvider: undefined,
      };
      await saveState(state);
      const { verifyTweetHandles, extractHandles } = await import("./social/xverify");
      const infos = await verifyTweetHandles(newText);
      const canPublish = infos.every((h) => h.status === "verified");
      const kb = new InlineKeyboard();
      kb.text("✏️ Editar tweet", `editTweet:${editFor.postId}`).text("✏️ Editar menciones", `editHandlesX:${editFor.postId}`).text("❌ Rechazar", `rejectTweet:${editFor.postId}`);
      if (!canPublish && extractHandles(newText).length > 0) {
        kb.row().text("✂️ Auto-arreglar", `fixTweet:${editFor.postId}`);
      }
      // Every handle in the edited text gets a 🔗 so the user can check in the
      // browser that the account exists (manual-edit = verification fallback).
      const editHandles = extractHandles(newText);
      if (editHandles.length > 0) {
        kb.row();
        editHandles.forEach((h, i) => {
          if (i > 0 && i % 4 === 0) kb.row();
          kb.url(`🔗 @${h}`, `https://x.com/${h}`);
        });
      }
      await ctx.reply(`✅ Tweet actualizado (${newText.length} caracteres):\n\n${linkifyHandles(newText)}${!canPublish && extractHandles(newText).length > 0 ? `\n\n⚠️ Resuelve las menciones antes de publicar.` : `\n\n✅ Tweet aprobado — preparando Facebook...`}`, {
        parse_mode: "Markdown",
        reply_markup: kb,
      });
      // Auto-advance to the Facebook flow once the tweet is clean.
      if (canPublish && published.social.facebook?.status !== "published") {
        try {
          await startFbFlow(published, state, ctx);
        } catch (err) {
          console.error("[bot] fb continuation failed:", err);
        }
      }
      return;
    }

    // Manual mention replacement (X) — fallback when verification fails.
    if (editFor.field === "xhandles") {
      const m = newText.match(/@?([A-Za-z0-9_]{1,15})\s*(?:→|->|=>|a)\s*@?([A-Za-z0-9_]{1,15})/i);
      if (!m) {
        await ctx.reply("⚠️ Formato: `@handle_actual → @handle_nuevo`. Envíalo de nuevo:", { parse_mode: "Markdown" });
        ctx.session.awaitingEditFor = editFor; // keep waiting
        return;
      }
      const bad = m[1];
      const good = m[2];
      const state = await freshState();
      const published = state.published.find((e) => e.id === editFor.postId);
      if (!published) {
        await ctx.reply("Post no encontrado en publicados.");
        return;
      }
      const xState = published.social.x;
      if (!xState?.tweet) {
        await ctx.reply("No hay tweet aprobado.");
        return;
      }
      xState.tweet = xState.tweet.replace(new RegExp(`@${bad}`, "gi"), `@${good}`);
      xState.status = "approved";
      await saveState(state);

      const { verifyTweetHandles, extractHandles, formatHandleStatus } = await import("./social/xverify");
      const infos = await verifyTweetHandles(xState.tweet);
      const canPublish = infos.every((h) => h.status === "verified");
      const statusLines = infos.map((h) => formatHandleStatus(h));
      const kb = new InlineKeyboard();
      kb.text("✏️ Editar tweet", `editTweet:${editFor.postId}`).text("✏️ Editar menciones", `editHandlesX:${editFor.postId}`).text("❌ Rechazar", `rejectTweet:${editFor.postId}`);
      if (!canPublish && extractHandles(xState.tweet).length > 0) {
        kb.row().text("✂️ Auto-arreglar", `fixTweet:${editFor.postId}`);
      }
      const checkHandles = extractHandles(xState.tweet);
      if (checkHandles.length > 0) {
        kb.row();
        checkHandles.forEach((h, i) => {
          if (i > 0 && i % 4 === 0) kb.row();
          kb.url(`🔗 @${h}`, `https://x.com/${h}`);
        });
      }
      await ctx.reply(
        `✅ Mención cambiada: @${escMarkdown(bad)} → *@${escMarkdown(good)}*\n\n${linkifyHandles(xState.tweet)}${statusLines.length ? `\n\n${statusLines.map((s) => escMarkdown(s)).join("\n")}` : ""}${canPublish ? "\n\n✅ Tweet aprobado — preparando Facebook..." : "\n\n⚠️ Resuelve las menciones antes de publicar."}`,
        { parse_mode: "Markdown", reply_markup: kb },
      );
      if (canPublish && published.social.facebook?.status !== "published") {
        try {
          await startFbFlow(published, state, ctx);
        } catch (err) {
          console.error("[bot] fb continuation failed:", err);
        }
      }
      return;
    }

    // Manual mention replacement (Facebook) — fallback when verification fails.
    if (editFor.field === "fbhandles") {
      const m = newText.match(/@?([A-Za-z0-9._-]{1,60})\s*(?:→|->|=>|a)\s*@?([A-Za-z0-9._-]{1,60})/i);
      if (!m) {
        await ctx.reply("⚠️ Formato: `@handle_actual → @handle_nuevo`. Envíalo de nuevo:", { parse_mode: "Markdown" });
        ctx.session.awaitingEditFor = editFor; // keep waiting
        return;
      }
      const bad = m[1];
      const good = m[2];
      const state = await freshState();
      const published = state.published.find((e) => e.id === editFor.postId);
      if (!published) {
        await ctx.reply("Post no encontrado en publicados.");
        return;
      }
      const fbState = published.social.facebook;
      if (!fbState?.tweet) {
        await ctx.reply("No hay texto de Facebook aprobado.");
        return;
      }
      fbState.tweet = fbState.tweet.replace(new RegExp(`@${bad}`, "gi"), `@${good}`);
      fbState.status = "approved";
      await saveState(state);
      await showFbApproval(published, state, ctx, fbState.tweet, published.caption ?? "", `✅ Mención cambiada: @${escMarkdown(bad)} → *@${escMarkdown(good)}*`);
      return;
    }

    // Facebook post edit (published post social flow)
    if (editFor.field === "fbtext") {
      if (newText.length > 1000) {
        await ctx.reply(`⚠️ El texto tiene ${newText.length} caracteres (máx 1000). Envíalo de nuevo más corto.`);
        ctx.session.awaitingEditFor = editFor; // keep waiting
        return;
      }
      const state = await freshState();
      const published = state.published.find((e) => e.id === editFor.postId);
      if (!published) {
        await ctx.reply("Post no encontrado en publicados.");
        return;
      }
      published.social.facebook = {
        status: "approved",
        tweet: newText,
        tweetProvider: undefined,
      };
      await saveState(state);
      await showFbApproval(published, state, ctx, newText, published.caption ?? "", "✅ Texto de Facebook actualizado");
      return;
    }

    const state = await freshState();
    const entry = state.pending.find((e) => e.id === editFor.postId);
    if (!entry) {
      await ctx.reply("Post no encontrado o ya procesado.");
      return;
    }

    const prevObjectPosition = entry.prepared.objectPosition;
    if (editFor.field === "title") {
      entry.article.title = newText;
    } else if (editFor.field === "desc") {
      entry.article.description = newText;
    } else {
      entry.article.content = newText;
    }
    // Re-run preparePost so the slug follows a new title; preserve objectPosition.
    entry.prepared = preparePost(entry.article, entry.post);
    entry.prepared.objectPosition = prevObjectPosition;
    await saveState(state);

    await ctx.reply(`✅ *${editFor.field === "title" ? "Título" : editFor.field === "desc" ? "Descripción" : "Contenido"}* actualizado.`, {
      parse_mode: "Markdown",
    });
    await notifyTelegram("approval", entry, ctx.chat?.id ?? state.chatId);
    return;
  }

  // 2) Feedback regeneration in progress (❌ Rechazar)
  const awaiting = ctx.session.awaitingFeedbackFor;
  if (!awaiting) return;

  const feedback = ctx.message.text;
  ctx.session.awaitingFeedbackFor = undefined;

  const state = await freshState();
  const entry = state.pending.find((e) => e.id === awaiting);
  if (!entry) {
    await ctx.reply("Post no encontrado o ya procesado.");
    return;
  }

  const statusMsg = await ctx.reply(`⏳ Regenerando con tu feedback...`);

  try {
    const isMixed = !entry.chosenProvider;
    if (isMixed) {
      // Mixed article: regenerate with BOTH providers, then show the picker again.
      const articles = await generateArticles(entry.post, feedback);
      const primary = articles.gemini ?? articles.deepseek!;
      entry.articles = articles;
      entry.article = primary;
      entry.prepared = preparePost(primary, entry.post);
      entry.chosenProvider = undefined;
      entry.chosenTitleProvider = undefined;
      entry.chosenContentProvider = undefined;
      entry.status = "pending";
      entry.feedback = feedback;
      entry.attempts += 1;
      entry.createdAt = new Date().toISOString();
      await saveState(state);
      await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, "✅ Posts regenerados con tu feedback. Elige la nueva combinación:");
      await notifyTelegram("approval-dual", entry, ctx.chat?.id ?? state.chatId);
    } else {
      const article = await generateArticle(entry.post, feedback, entry.chosenProvider);
      const prepared = preparePost(article, entry.post);
      entry.article = article;
      entry.prepared = prepared;
      entry.status = "pending";
      entry.feedback = feedback;
      entry.attempts += 1;
      entry.createdAt = new Date().toISOString();
      await saveState(state);

      await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, "✅ Post regenerado. ¿Guardar este feedback como instrucción para futuros posts?", {
        reply_markup: new InlineKeyboard()
          .text("✅ Sí, guardar", `saveinst:${awaiting}`)
          .text("❌ No", "noinst"),
      });
    }
  } catch (err) {
    console.error("[bot] regenerate failed:", err);
    await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, `❌ Error al regenerar: ${(err as Error).message}`);
  }
});

bot.callbackQuery(/^saveinst:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const state = await freshState();
  const entry = state.pending.find((e) => e.id === postId);
  if (!entry?.feedback) {
    await ctx.answerCallbackQuery({ text: "No hay feedback para guardar" });
    return;
  }
  const instruction = await addInstruction(entry.feedback, "feedback");
  await ctx.answerCallbackQuery({ text: "Instrucción guardada" });
  await ctx.reply(`✅ Guardada como instrucción ${instruction.id}\n"${entry.feedback}"\n\nEl post regenerado sigue en cola. Usa /aprobar o los botones para publicarlo.`);
});

bot.callbackQuery(/^noinst$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  await ctx.answerCallbackQuery({ text: "OK" });
});

// ---- Manual approval via command ----

bot.command("aprobar", async (ctx) => {
  if (!isAllowed(ctx)) return;
  const state = await freshState();
  if (state.pending.length === 0) {
    await ctx.reply("No hay posts pendientes.");
    return;
  }
  await publishEntry(state.pending[0], state, ctx);
});

bot.command("rechazar", async (ctx) => {
  if (!isAllowed(ctx)) return;
  const state = await freshState();
  if (state.pending.length === 0) {
    await ctx.reply("No hay posts pendientes.");
    return;
  }
  const feedback = ctx.match?.trim();
  if (!feedback) {
    await ctx.reply("Uso: /rechazar <feedback>");
    return;
  }
  const entry = state.pending[0];
  ctx.session.awaitingFeedbackFor = entry.id;
  await ctx.reply("Procesando feedback...");
});

// ---- Error handler ----

bot.catch((err) => {
  console.error("[bot] error:", err.error);
});

console.log("[bot] starting Telegram bot...");

// Seed the verified-lookup caches from persisted state.
(async () => {
  try {
    const state = await loadState();
    const { seedKnownArtistHandles } = await import("./social/xverify");
    const { seedKnownFbPages } = await import("./social/fbverify");
    seedKnownArtistHandles(state.knownXHandles);
    seedKnownFbPages(state.knownFbPages);
    console.log("[bot] caches sembrados:", Object.keys(state.knownXHandles ?? {}).length, "artistas,", Object.keys(state.knownFbPages ?? {}).length, "páginas FB");
  } catch (err) {
    console.warn("[bot] no se pudieron sembrar caches:", (err as Error).message);
  }
})();

bot.start({ onStart: (me) => console.log(`[bot] running as @${me.username}`) });
