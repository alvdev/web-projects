import { existsSync } from "node:fs";
import { mkdir, readFile, writeFile } from "node:fs/promises";
import { dirname, join } from "node:path";
import { BLOG_ROOT, TRANSLATION_LOCALES, copyDirAssets, stripSlugFrontmatter } from "./content";
import { translateFileContent } from "./llm";

/**
 * Validate that a translated file has a well-formed, closed frontmatter block.
 * The LLM occasionally truncates or forgets the closing `---`, which would
 * break the whole collection. Returns an error message or null.
 */
export function validateTranslation(file: string, kind: "blog" | "service" | "section"): string | null {
  const trimmed = file.trimStart();
  if (!trimmed.startsWith("---\n")) return "frontmatter opening delimiter missing";
  const body = trimmed.slice(4);
  const closeIdx = body.indexOf("\n---");
  if (closeIdx === -1) return "frontmatter closing delimiter missing";
  const fm = body.slice(0, closeIdx);
  const required = kind === "blog" ? ["title", "description", "cover", "pubDate", "taxonomy"] : kind === "service" ? ["title", "description", "order"] : ["title"];
  const missing = required.filter((key) => !new RegExp(`^${key}:`, "m").test(fm));
  if (missing.length > 0) return `frontmatter missing fields: ${missing.join(", ")}`;
  return null;
}

/**
 * Faithfully translate a published Spanish blog post into every locale
 * (en/it/fr/pt) and write the localized MDX files next to their assets.
 * Localized copies reuse the Spanish slug folder so the URL only gains the
 * locale prefix (/en/blog/<slug>). Already-translated locales are skipped.
 *
 * @returns paths of the files written (empty if everything already existed)
 */
export async function translatePostBySlug(
  slug: string,
  locales: readonly string[] = TRANSLATION_LOCALES,
): Promise<string[]> {
  const esMdx = join(BLOG_ROOT, slug, "index.mdx");
  if (!existsSync(esMdx)) {
    throw new Error(`Spanish post not found: ${esMdx}`);
  }
  const source = await readFile(esMdx, "utf8");
  const written: string[] = [];

  for (const locale of locales) {
    const localeMdx = join(BLOG_ROOT, locale, slug, "index.mdx");
    if (existsSync(localeMdx)) {
      console.log(`[translate] skip ${locale}/${slug} (already exists)`);
      continue;
    }
    let final = "";
    for (let attempt = 1; attempt <= 2; attempt++) {
      const translated = await translateFileContent(source, locale, "blog");
      final = stripSlugFrontmatter(translated);
      const error = validateTranslation(final, "blog");
      if (!error) break;
      console.warn(`[translate] ${locale}/${slug} attempt ${attempt} invalid (${error}), retrying...`);
      if (attempt === 2) throw new Error(`translation validation failed for ${locale}/${slug}: ${error}`);
    }
    await mkdir(dirname(localeMdx), { recursive: true });
    await writeFile(localeMdx, final, "utf8");
    await copyDirAssets(join(BLOG_ROOT, slug), join(BLOG_ROOT, locale, slug));
    written.push(localeMdx);
  }

  return written;
}