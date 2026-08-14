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
import type { PendingEntry, PendingState, PreparedPost } from "./types";
import { rm } from "node:fs/promises";
import { join } from "node:path";

interface SessionData {
  awaitingFeedbackFor?: string;
  awaitingEditFor?: { postId: string; field: "title" | "desc" | "content" | "tweet" };
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

bot.callbackQuery(/^social:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  const state = await freshState();
  const published = state.published.find((e) => e.id === postId);
  if (!published) {
    await ctx.answerCallbackQuery({ text: "Post no encontrado en publicados" });
    return;
  }

  const xState = published.social.x ?? { status: "queued" };
  published.social.x = xState;

  await ctx.answerCallbackQuery({ text: "Generando tweets..." });
  await ctx.reply(`⏳ Generando propuestas de tweet para *"${published.title}"* (Gemini + DeepSeek)...`, {
    parse_mode: "Markdown",
  });

  try {
    // Rebuild the PreparedPost so tweet.ts has full data (title, desc, slug)
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

    const tweets = await generateTweets(
      source as PreparedPost,
      published.caption ?? pendingEntry?.post.caption ?? "",
      published.postTimestamp ?? pendingEntry?.post.timestamp,
    );
    xState.status = "queued";
    xState.tweet = undefined;
    await saveState(state);

    // Verify @handles in both versions
    const { verifyTweetHandles, formatHandleStatus } = await import("./social/xverify");
    const verifyFor = async (t: string): Promise<string[]> => {
      const infos = await verifyTweetHandles(t);
      return infos.map((i) => formatHandleStatus(i));
    };

    const lines: string[] = ["Selecciona la versión del tweet:"];
    if (tweets.gemini) {
      lines.push("", `📝 *GEMINI:*\n${escMarkdown(tweets.gemini)}`);
      const statuses = await verifyFor(tweets.gemini);
      if (statuses.length) lines.push("", ...statuses.map((s) => escMarkdown(s)));
    }
    if (tweets.deepseek) {
      lines.push("", `📝 *DEEPSEEK:*\n${escMarkdown(tweets.deepseek)}`);
      const statuses = await verifyFor(tweets.deepseek);
      if (statuses.length) lines.push("", ...statuses.map((s) => escMarkdown(s)));
    }
    if (tweets.gemini && tweets.deepseek && tweets.gemini === tweets.deepseek) {
      lines.push("", "⚠️ Ambas versiones son idénticas.");
    }

    // Dual picker buttons
    const kb = new InlineKeyboard();
    if (tweets.gemini) kb.text("❤️ Tweet Gemini", `pickTweet:gemini:${postId}`);
    if (tweets.deepseek) kb.text("💙 Tweet DeepSeek", `pickTweet:deepseek:${postId}`);

    await ctx.reply(lines.join("\n"), {
      parse_mode: "Markdown",
      reply_markup: kb,
    });
  } catch (err) {
    console.error("[bot] tweet generation failed:", err);
    await ctx.reply(`❌ Error generando tweets: ${(err as Error).message}`);
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

  // Regenerate the chosen tweet (or use cached) — regenerate for accuracy
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

  const statusMsg = await ctx.reply(`⏳ Generando tweet definitivo con ${provider}...`);
  try {
    const prompt = buildTweetPrompt(source as PreparedPost, published.caption ?? "", published.postTimestamp ?? "");
    const text = await generateTextCompletion(prompt, provider, { temperature: 0.8, maxTokens: 4000 });
    const body = text
      .trim()
      .replace(/^["']|["']$/g, "")
      .replace(/https?:\/\/\S+/g, "")
      .replace(/\s+/g, " ")
      .trim();
    const url = `https://urbanstylepublicity.com/blog/${published.slug}`;
    const maxBody = 280 - 23; // URL counts as 23 chars on X (t.co)
    const trimmedBody = body.length > maxBody ? body.slice(0, maxBody) : body;
    const tweet = `${trimmedBody} ${url}`.trim();

    xState.status = "approved";
    xState.tweet = tweet;
    xState.tweetProvider = provider;
    published.social.x = xState;
    await saveState(state);

    // Verify @handles in the final tweet; for invalid/suspicious handles,
    // look up the official handle via Google-search-grounded Gemini and show
    // the comparison so the user can decide.
    const { verifyTweetHandles, formatHandleStatus, extractHandles, findOfficialHandle } = await import("./social/xverify");
    const handleInfos = await verifyTweetHandles(tweet);
    const hasInvalid = handleInfos.some((h) => h.status !== "verified");
    const statusLines = handleInfos.map((h) => formatHandleStatus(h));
    const suggestions: Record<string, string> = {}; // badHandle -> official handle

    if (hasInvalid && extractHandles(tweet).length > 0) {
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
            suggestions[info.handle] = official.handle;
            statusLines.push(
              "",
              `↔️ @${info.handle} → *@${official.handle}* (${official.followers ? (official.followers >= 1_000_000 ? (official.followers / 1_000_000).toFixed(1) + "M" : official.followers >= 1000 ? (official.followers / 1000).toFixed(0) + "K" : String(official.followers)) : "?"} seguidores${official.isVerified ? ", verificado" : ""})`,
            );
          }
        }
      }
    }

    const kb = new InlineKeyboard()
      .text("📤 Publicar en X", `postX:${postId}`)
      .text("✏️ Editar tweet", `editTweet:${postId}`)
      .row()
      .text("❌ Rechazar", `rejectTweet:${postId}`);
    const suggestionEntries = Object.entries(suggestions);
    // First ✅ Usar pairs with ❌ on the same row; further ones pair 2 per row.
    for (let i = 0; i < suggestionEntries.length; i++) {
      const [bad, good] = suggestionEntries[i];
      if (i % 2 === 1) kb.row();
      kb.text(`✅ Usar @${good}`, `useHandle:${postId}:${bad}:${good}`);
    }
    if (hasInvalid && suggestionEntries.length === 0 && extractHandles(tweet).length > 0) {
      kb.row().text("✂️ Auto-arreglar", `fixTweet:${postId}`);
    }

    await ctx.api.editMessageText(
      ctx.chat!.id,
      statusMsg.message_id,
      `✅ Tweet listo (${tweet.length} caracteres):\n\n${escMarkdown(tweet)}${statusLines.length ? `\n\n${statusLines.map((s) => escMarkdown(s)).join("\n")}` : ""}`,
      {
        parse_mode: "Markdown",
        reply_markup: kb,
      },
    );
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

  await ctx.answerCallbackQuery({ text: `@${bad} → @${good}` });
  await ctx.reply(
    `✅ Menciones corregidas: @${bad} → @${good}\n\n${escMarkdown(replaced)}`,
    {
      parse_mode: "Markdown",
      reply_markup: new InlineKeyboard()
        .text("📤 Publicar en X", `postX:${postId}`)
        .text("✏️ Editar tweet", `editTweet:${postId}`)
        .row()
        .text("❌ Rechazar", `rejectTweet:${postId}`),
    },
  );
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
  if (!xState?.tweet || !xState.tweetProvider) {
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

    const text = await generateTextCompletion(prompt, xState.tweetProvider, { temperature: 0.8, maxTokens: 4000 });
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
    const kb = new InlineKeyboard()
      .text("📤 Publicar en X", `postX:${postId}`)
      .text("✏️ Editar tweet", `editTweet:${postId}`)
      .row()
      .text("❌ Rechazar", `rejectTweet:${postId}`);
    if (newInfos.some((h) => h.status !== "verified") && extractHandles(newTweet).length > 0) {
      kb.row().text("✂️ Auto-arreglar", `fixTweet:${postId}`);
    }

    await ctx.api.editMessageText(
      ctx.chat!.id,
      statusMsg.message_id,
      `✂️ Tweet regenerado sin menciones inválidas (${newTweet.length} caracteres):\n\n${escMarkdown(newTweet)}${statusLines.length ? `\n\n${statusLines.map((s) => escMarkdown(s)).join("\n")}` : ""}`,
      {
        parse_mode: "Markdown",
        reply_markup: kb,
      },
    );
  } catch (err) {
    console.error("[bot] fixTweet failed:", err);
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

  await ctx.answerCallbackQuery({ text: "Publicando en X..." });
  const statusMsg = await ctx.reply(`⏳ Publicando tweet en X (@pegadacarteles) vía Buffer...\n\n${xState.tweet}`);

  try {
    const { getXChannel, createPost } = await import("./social/buffer");
    const channel = await getXChannel();
    const imageUrl = getPostImageUrl(published.slug);

    const post = await createPost(channel.id, xState.tweet, imageUrl ?? undefined);

    xState.status = "published";
    xState.publishedAt = new Date().toISOString();
    xState.error = undefined;
    await saveState(state);

    const link = post.externalLink ?? `https://x.com/pegadacarteles`;
    await ctx.api.editMessageText(
      ctx.chat!.id,
      statusMsg.message_id,
      `✅ *Publicado en X:*\n\n${escMarkdown(xState.tweet)}\n\n${escMarkdown(link)}${imageUrl ? `\n\n🖼️ Imagen: ${escMarkdown(imageUrl)}` : ""}`,
      { parse_mode: "Markdown" },
    );
    await sendAlert(`[Urban Sync] Tweet publicado en X: ${published.title}`, `${xState.tweet}\n\n${link}`);
  } catch (err) {
    console.error("[bot] postX failed:", err);
    xState.status = "failed";
    xState.error = (err as Error).message;
    await saveState(state);
    await ctx.api.editMessageText(ctx.chat!.id, statusMsg.message_id, `❌ *Error publicando en X:*\n${(err as Error).message}`);
    await sendAlert("[Urban Sync] Error publicando tweet en X", `${(err as Error).message}\n\nPost: ${published.title}`);
  }
});

bot.callbackQuery(/^editTweet:(.+)$/, async (ctx) => {
  if (!isAllowed(ctx)) return;
  const postId = ctx.match[1];
  await ctx.answerCallbackQuery({ text: "Envía el nuevo tweet..." });
  await ctx.reply("✏️ Envía el texto del tweet (máx 280 caracteres):");
  ctx.session.awaitingEditFor = { postId, field: "tweet" };
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
      await ctx.reply(`✅ Tweet actualizado (${newText.length} caracteres):\n\n${escMarkdown(newText)}`, {
        parse_mode: "Markdown",
        reply_markup: new InlineKeyboard()
          .text("📤 Publicar en X", `postX:${editFor.postId}`)
          .text("✏️ Editar tweet", `editTweet:${editFor.postId}`),
      });
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
bot.start({ onStart: (me) => console.log(`[bot] running as @${me.username}`) });
