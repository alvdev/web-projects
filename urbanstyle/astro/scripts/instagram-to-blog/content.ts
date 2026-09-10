import { mkdir, writeFile, readdir, copyFile, stat } from "node:fs/promises";
import { dirname, join } from "node:path";
import type { LlmArticle, NewPost, PreparedPost } from "./types";

export const BLOG_ROOT = `${process.cwd()}/src/content/blog`;

export const TRANSLATION_LOCALES = ["en", "it", "fr", "pt"] as const;

const BAD_TITLE_ENDINGS = new Set([
  "de", "del", "para", "con", "en", "por", "a", "al", "la", "el", "los", "las",
  "un", "una", "unos", "unas", "y", "que", "su", "sus", "mi", "mis", "tu", "tus",
  "sin", "sobre", "entre", "hacia", "hasta", "desde", "tras",
]);

function lastWord(text: string): string {
  return text.trim().split(/\s+/).pop() ?? "";
}

export function validateTitleEnding(title: string): string | null {
  const word = lastWord(title).toLowerCase();
  return BAD_TITLE_ENDINGS.has(word) ? word : null;
}

function generateSlug(text: string): string {
  const MAX_SLUG_LENGTH = 100;
  const cleaned = text
    .toString()
    .toLowerCase()
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .replace(/\s+/g, "-")
    .replace(/[^\w\-]+/g, "")
    .replace(/\-\-+/g, "-")
    .replace(/^-+/, "")
    .replace(/-+$/, "");

  let slug = cleaned;
  if (slug.length > MAX_SLUG_LENGTH) {
    // Truncate at the last word boundary (dash) before the cap — never mid-word
    const cut = cleaned.lastIndexOf("-", MAX_SLUG_LENGTH);
    slug = cleaned.substring(0, cut > 0 ? cut : MAX_SLUG_LENGTH).replace(/-+$/, "");
  }

  // Never end with a dangling preposition/article — walk back to the previous
  // word boundary until the last segment is a meaningful word.
  while (slug.length > 0) {
    const last = slug.split("-").pop() ?? "";
    if (!BAD_TITLE_ENDINGS.has(last)) break;
    const prev = slug.lastIndexOf("-");
    if (prev <= 0) break;
    slug = slug.substring(0, prev).replace(/-+$/, "");
  }

  return slug;
}

function formatPubDate(timestamp: string): string {
  const date = new Date(timestamp);
  const pad = (n: number) => String(n).padStart(2, "0");
  return `${pad(date.getDate())}-${pad(date.getMonth() + 1)}-${date.getFullYear()} ${pad(date.getHours())}:${pad(date.getMinutes())}`;
}

function escapeMdx(value: string): string {
  // Frontmatter is written as YAML single-quoted scalars: apostrophes are
  // escaped by doubling (''), backslashes are literal — never backslash-escape.
  return value.replace(/'/g, "''");
}

export function preparePost(article: LlmArticle, post: NewPost): PreparedPost {
  // Safety net: normalize work-title formatting regardless of provider output.
  const fixEscapes = (s: string) => s.replace(/\\"/g, '"').replace(/\\'/g, "'");
  let title = fixEscapes(article.title).trim();
  let description = fixEscapes(article.description).trim();
  let content = fixEscapes(article.content);

  // content: «Título» -> *"Título"* ; title/desc: «Título» -> "Título"
  content = content.replace(/«([^»]+)»/g, '*"$1"*');
  title = title.replace(/«([^»]+)»/g, '"$1"');
  description = description.replace(/«([^»]+)»/g, '"$1"');

  const titleStart = title.toLowerCase().split(" ").slice(0, 3).join(" ");
  const descStart = description.toLowerCase().split(" ").slice(0, 3).join(" ");
  if (titleStart === descStart) {
    description = "Explora cómo " + description.charAt(0).toLowerCase() + description.slice(1);
  }

  const slug = generateSlug(title);
  const basePath = `${BLOG_ROOT}/${slug}`;

  return {
    title,
    description,
    content,
    tags: article.tags,
    category: article.category,
    slug,
    pubDate: formatPubDate(post.timestamp),
    basePath,
    media_url: post.mediaUrl,
    igMediaId: post.id,
    mdxPath: `${basePath}/index.mdx`,
    imagePath: `${basePath}/header.jpg`,
  };
}

export function buildMdx(data: PreparedPost): string {
  return `---
title: '${escapeMdx(data.title)}'
description: '${escapeMdx(data.description)}'
cover:
  url: './header.jpg'
  alt: '${escapeMdx(data.title)}'
  objectPosition: '${data.objectPosition ?? "center"}'
slug: '${escapeMdx(data.slug)}'
taxonomy:
  categories: ['${escapeMdx(data.category)}']
  tags: [${data.tags.map((t) => `'${escapeMdx(t)}'`).join(", ")}]
pubDate: '${data.pubDate}'
---

![${data.title}](./header.jpg)

${data.content}
`;
}

export async function downloadImage(url: string, dest: string): Promise<void> {
  const res = await fetch(url);
  if (!res.ok) throw new Error(`Image download failed: ${res.status} ${res.statusText}`);
  const buffer = Buffer.from(await res.arrayBuffer());
  await mkdir(dirname(dest), { recursive: true });
  await writeFile(dest, buffer);
}

export async function writePostFiles(data: PreparedPost): Promise<void> {
  await mkdir(dirname(data.mdxPath), { recursive: true });
  await writeFile(data.mdxPath, buildMdx(data), "utf8");
  await downloadImage(data.media_url, data.imagePath);
}

/**
 * Localized blog copies must NOT carry a `slug` frontmatter field: the glob
 * loader uses it as the entry id, so a translated post with the same slug
 * would collide with the Spanish original. Path-derived ids (en/<slug>) are
 * unique and keep the same public URL once the locale prefix is stripped.
 */
export function stripSlugFrontmatter(file: string): string {
  return file.replace(/^---\n([\s\S]*?)\n---/, (_m, fm: string) => {
    const cleaned = fm
      .split("\n")
      .filter(line => !/^\s*slug:\s*/.test(line))
      .join("\n");
    return `---\n${cleaned}\n---`;
  });
}

export async function copyDirAssets(fromDir: string, toDir: string): Promise<void> {
  let entries: string[] = [];
  try {
    entries = await readdir(fromDir);
  } catch {
    return;
  }
  await mkdir(toDir, { recursive: true });
  for (const name of entries) {
    if (/\.(md|mdx)$/i.test(name)) continue;
    const src = join(fromDir, name);
    const info = await stat(src).catch(() => null);
    if (!info) continue;
    if (info.isDirectory()) {
      await copyDirAssets(src, join(toDir, name));
    } else {
      await copyFile(src, join(toDir, name));
    }
  }
}

/**
 * Write the faithfully translated MDX for one locale of a published post and
 * mirror all media assets from the Spanish post folder.
 * Returns the path of the written file.
 */
export async function writeLocalizedPostFiles(
  prepared: PreparedPost,
  locale: string,
  translatedFile: string,
): Promise<string> {
  const localeDir = `${BLOG_ROOT}/${locale}/${prepared.slug}`;
  const localeMdx = `${localeDir}/index.mdx`;
  await mkdir(localeDir, { recursive: true });
  await writeFile(localeMdx, stripSlugFrontmatter(translatedFile), "utf8");
  await copyDirAssets(dirname(prepared.mdxPath), localeDir);
  return localeMdx;
}
