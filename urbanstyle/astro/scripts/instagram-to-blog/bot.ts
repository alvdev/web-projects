import { Bot, Context, InlineKeyboard, session, type SessionFlavor } from "grammy";
import { loadState, saveState, bumpCache } from "./state";
import { formatEntryPreview, approvalKeyboard } from "./telegram";
import { writePostFiles, BLOG_ROOT } from "./content";
import { buildSite, uploadDist, removeRemoteDir } from "./deploy";
import { generateArticle, generateArticles } from "./llm";
import { preparePost } from "./content";
import { addInstruction, loadInstructions, removeInstruction } from "./instructions";
import { sendAlert } from "./mailer";
import { notifyTelegram } from "./telegram";
import type { PendingEntry, PendingState } from "./types";
import { rm } from "node:fs/promises";
import { join } from "node:path";

interface SessionData {
  awaitingFeedbackFor?: string;
  awaitingEditFor?: { postId: string; field: "title" | "desc" | "content" };
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
    const { uploaded, skipped, pending } = await uploadDist((done, pendingCount) => {
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
      socialStatus: "queued",
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
  published.socialStatus = "queued";
  await saveState(state);
  await ctx.answerCallbackQuery({ text: "En cola para redes sociales" });
  await ctx.reply(`▶️ *"${published.title}"* quedó en cola para publicarse en redes sociales (Facebook, X, Google Business Profile). El flujo de redes se implementa en la siguiente fase.`, {
    parse_mode: "Markdown",
  });
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
  await ctx.reply(`📖 *Contenido completo* (${entry.prepared.title}):\n\n${entry.prepared.content}`, {
    parse_mode: "Markdown",
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
