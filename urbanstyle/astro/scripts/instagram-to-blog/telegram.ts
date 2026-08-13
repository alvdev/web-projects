import { Bot, InlineKeyboard } from "grammy";
import type { PendingEntry } from "./types";
import { validateTitleEnding } from "./content";

export class TelegramError extends Error {}

let bot: Bot | null = null;

export function getBot(): Bot {
  if (bot) return bot;
  const token = process.env.TELEGRAM_BOT_TOKEN ?? "";
  if (!token) throw new TelegramError("TELEGRAM_BOT_TOKEN not set");
  bot = new Bot(token);
  return bot;
}

function truncate(text: string, max: number): string {
  return text.length > max ? text.slice(0, max - 1) + "…" : text;
}

function escMarkdown(text: string): string {
  return text.replace(/([_*[\]`])/g, "\\$1");
}

export function formatEntryPreview(entry: PendingEntry): string {
  const p = entry.prepared;
  const lines = [
    `📝 *${escMarkdown(p.title)}*`,
  ];

  const badEnding = validateTitleEnding(p.title);
  if (badEnding) {
    lines.push(`⚠️ *El título podría estar incompleto* — termina en "${badEnding}"`);
  }

  lines.push(
    "",
    `*Categoría:* ${escMarkdown(p.category)}`,
    `*Tags:* ${escMarkdown(p.tags.join(", "))}`,
    `*Fecha:* ${escMarkdown(p.pubDate)}`,
    `*Posición imagen:* ${p.objectPosition ?? "center"}`,
    `*Slug:* /blog/${escMarkdown(p.slug)}`,
    "",
    `*Descripción:* ${escMarkdown(p.description)}`,
    "",
    `*Contenido:*`,
    truncate(p.content.replace(/[#*`_>]/g, ""), 800),
    "",
    `_Intento ${entry.attempts}_`,
  );
  return lines.join("\n");
}

export function approvalKeyboard(entry: PendingEntry): InlineKeyboard {
  const id = entry.id;
  return new InlineKeyboard()
    .text("✅ Aprobar", `approve:${id}`)
    .text("❌ Rechazar", `reject:${id}`)
    .row()
    .text("✏️ Título", `edit:title:${id}`)
    .text("✏️ Descripción", `edit:desc:${id}`)
    .text("✏️ Contenido", `edit:content:${id}`)
    .row()
    .text("📐 Top", `crop:top:${id}`)
    .text("📐 Centro", `crop:center:${id}`)
    .text("📐 Abajo", `crop:bottom:${id}`)
    .row()
    .text("📖 Ver contenido completo", `preview:${id}`);
}

export function dualPickKeyboard(entry: PendingEntry): InlineKeyboard {
  const id = entry.id;
  const g = entry.articles?.gemini;
  const d = entry.articles?.deepseek;
  const kb = new InlineKeyboard();
  if (g && d) {
    kb.text("🧩 Gemini tít + Gemini cont", `mix:gemini:gemini:${id}`)
      .text("🧩 Gemini tít + DeepSeek cont", `mix:gemini:deepseek:${id}`)
      .row()
      .text("🧩 DeepSeek tít + Gemini cont", `mix:deepseek:gemini:${id}`)
      .text("🧩 DeepSeek tít + DeepSeek cont", `mix:deepseek:deepseek:${id}`);
  } else {
    if (g) kb.text("❤️ Gemini", `pick:gemini:${id}`);
    if (d) kb.text("💙 DeepSeek", `pick:deepseek:${id}`);
  }
  return kb;
}

function formatDualPreview(entry: PendingEntry): string {
  const lines: string[] = [];
  const g = entry.articles?.gemini;
  const d = entry.articles?.deepseek;

  if (g) {
    const badG = validateTitleEnding(g.title);
    lines.push(`📝 *Gemini:* ${escMarkdown(g.title)}${badG ? ` ⚠️ termina en "${badG}"` : ""}`);
  } else {
    lines.push("❌ *Gemini:* falló la generación");
  }
  if (d) {
    const badD = validateTitleEnding(d.title);
    lines.push(`📝 *DeepSeek:* ${escMarkdown(d.title)}${badD ? ` ⚠️ termina en "${badD}"` : ""}`);
  } else {
    lines.push("❌ *DeepSeek:* falló la generación");
  }

  lines.push("", "Elige la mejor versión (título y contenido por separado):");
  return lines.join("\n");
}

function formatFullArticle(entry: PendingEntry, provider: "gemini" | "deepseek"): string {
  const a = entry.articles?.[provider];
  if (!a) return `❌ *${provider}*: versión no disponible`;
  const lines = [
    `📝 *${provider.toUpperCase()}* — artículo completo`,
    "",
    `*Título:* ${escMarkdown(a.title)}`,
    "",
    `*Descripción:* ${escMarkdown(a.description)}`,
    "",
    `*Categoría:* ${escMarkdown(a.category)}`,
    `*Tags:* ${escMarkdown(a.tags.join(", "))}`,
    "",
    `*Contenido:*`,
    a.content,
  ];
  return lines.join("\n");
}

export async function notifyTelegram(
  kind: "approval" | "approval-dual" | "published" | "error",
  entry: PendingEntry | { title: string; error?: string },
  chatId?: number,
): Promise<void> {
  const cid = chatId ?? Number(process.env.TELEGRAM_CHAT_ID ?? 0);
  if (!cid) throw new TelegramError("TELEGRAM_CHAT_ID not set");

  const b = getBot();
  const api = b.api;

  if (kind === "approval-dual" && "prepared" in entry) {
    const caption = truncate(formatDualPreview(entry), 1024);
    try {
      await api.sendPhoto(cid, entry.post.mediaUrl, {
        parse_mode: "Markdown",
        caption,
        reply_markup: dualPickKeyboard(entry),
      });
    } catch {
      await api.sendMessage(cid, caption, {
        parse_mode: "Markdown",
        reply_markup: dualPickKeyboard(entry),
      });
    }
    // Send both full articles so the reviewer can pick based on the entire
    // article, not just the title.
    if (entry.articles?.gemini) {
      await api.sendMessage(cid, truncate(formatFullArticle(entry, "gemini"), 4096), {
        parse_mode: "Markdown",
      });
    }
    if (entry.articles?.deepseek) {
      await api.sendMessage(cid, truncate(formatFullArticle(entry, "deepseek"), 4096), {
        parse_mode: "Markdown",
      });
    }
  } else if (kind === "approval" && "prepared" in entry) {
    const caption = formatEntryPreview(entry);
    const mediaUrl = entry.post.mediaUrl;
    // Send the actual header photo so the reviewer sees the image before approving
    try {
      await api.sendPhoto(cid, mediaUrl, {
        parse_mode: "Markdown",
        caption: truncate(caption, 1024),
        reply_markup: approvalKeyboard(entry),
      });
    } catch {
      // Fallback to text-only if the photo cannot be fetched/attached
      await api.sendMessage(cid, caption, {
        parse_mode: "Markdown",
        reply_markup: approvalKeyboard(entry),
      });
    }
  } else if (kind === "published" && "prepared" in entry) {
    await api.sendMessage(cid, `✅ *Publicado:* ${entry.prepared.title}\n\nURL: /blog/${entry.prepared.slug}`, {
      parse_mode: "Markdown",
    });
  } else {
    const errorText = "error" in entry && entry.error ? entry.error : "title" in entry ? entry.title : "unknown error";
    await api.sendMessage(cid, `❌ *Error:* ${errorText}`, {
      parse_mode: "Markdown",
    });
  }
}
