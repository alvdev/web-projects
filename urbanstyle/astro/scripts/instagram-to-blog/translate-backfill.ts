/**
 * Batch-translate existing Spanish content into en/it/fr/pt with the LLM,
 * using the same faithful-translation rules as the live pipeline.
 *
 * Usage (from the repo root):
 *   bun run scripts/instagram-to-blog/translate-backfill.ts
 *   bun run scripts/instagram-to-blog/translate-backfill.ts --targets blog
 *   bun run scripts/instagram-to-blog/translate-backfill.ts --targets blog,services,sections --locales en,fr
 *   bun run scripts/instagram-to-blog/translate-backfill.ts --dry-run
 *
 * Already-translated files are always skipped, so re-running is safe.
 */
import { existsSync } from "node:fs";
import { mkdir, readFile, readdir, writeFile, copyFile, rename } from "node:fs/promises";
import { dirname, join } from "node:path";
import { BLOG_ROOT, TRANSLATION_LOCALES, copyDirAssets, stripSlugFrontmatter } from "./content";
import { translateFileContent, translateJsonContent } from "./llm";
import { translatePostBySlug, validateTranslation } from "./translate";
import { provincesMetadata, allSpainCities } from "../../src/data/cities/allSpainCities.js";

const SERVICES_ROOT = `${process.cwd()}/src/content/services`;
const SECTIONS_ROOT = `${process.cwd()}/src/content/sections`;
const LOCALIZED_CITIES_ROOT = `${process.cwd()}/src/data/cities/localized`;
const LOCALE_DIRS = new Set<string>(TRANSLATION_LOCALES);

const CITIES_BATCH_SIZE = 10;

interface Args {
  targets: Set<string>;
  locales: string[];
  dryRun: boolean;
}

function parseArgs(argv: string[]): Args {
  const read = (flag: string, def: string): string => {
    const found = argv.find((a) => a.startsWith(`${flag}=`));
    if (found) return found.slice(flag.length + 1);
    const idx = argv.indexOf(flag);
    if (idx !== -1 && argv[idx + 1] && !argv[idx + 1].startsWith("--")) return argv[idx + 1];
    return def;
  };
  const args: Args = {
    targets: new Set(read("--targets", "blog,services,sections,cities").split(",").filter(Boolean)),
    locales: read("--locales", TRANSLATION_LOCALES.join(",")).split(",").filter(Boolean),
    dryRun: argv.includes("--dry-run"),
  };
  return args;
}

async function listDirs(root: string, skipLocaleDirs = true): Promise<string[]> {
  const entries = await readdir(root, { withFileTypes: true });
  return entries
    .filter((e) => e.isDirectory())
    .map((e) => e.name)
    .filter((name) => !skipLocaleDirs || !LOCALE_DIRS.has(name));
}

async function listMdFiles(dir: string): Promise<string[]> {
  const out: string[] = [];
  const entries = await readdir(dir, { withFileTypes: true });
  for (const e of entries) {
    if (LOCALE_DIRS.has(e.name)) continue;
    const full = join(dir, e.name);
    if (e.isDirectory()) {
      out.push(...(await listMdFiles(full)));
    } else if (/\.(md|mdx)$/i.test(e.name)) {
      out.push(full);
    }
  }
  return out;
}

async function translateBlog(args: Args): Promise<number> {
  const slugs = await listDirs(BLOG_ROOT);
  let written = 0;
  for (const slug of slugs) {
    if (!existsSync(join(BLOG_ROOT, slug, "index.mdx"))) continue;
    if (args.dryRun) {
      console.log(`[dry-run] blog/${slug} → ${args.locales.join(",")}`);
      written++;
      continue;
    }
    const paths = await translatePostBySlug(slug, args.locales);
    written += paths.length;
  }
  return written;
}

async function translateFileWithRetry(
  source: string,
  locale: string,
  kind: "service" | "section",
  label: string,
): Promise<string> {
  let final = "";
  for (let attempt = 1; attempt <= 2; attempt++) {
    const translated = await translateFileContent(source, locale, kind);
    final = stripSlugFrontmatter(translated);
    const error = validateTranslation(final, kind);
    if (!error) return final;
    console.warn(`[translate] ${label} attempt ${attempt} invalid (${error}), retrying...`);
  }
  throw new Error(`translation validation failed for ${label}`);
}

async function translateServices(args: Args): Promise<number> {
  const serviceDirs = await listDirs(SERVICES_ROOT);
  let written = 0;
  for (const slug of serviceDirs) {
    const esMdx = join(SERVICES_ROOT, slug, "index.mdx");
    if (!existsSync(esMdx)) continue;
    const source = await readFile(esMdx, "utf8");
    // Carousel caption files inside the service folder (carousel1.md, carousel2.md, ...)
    const carouselFiles = (await listMdFiles(join(SERVICES_ROOT, slug))).filter((f) => f.endsWith("index.mdx") === false);
    for (const locale of args.locales) {
      const outMdx = join(SERVICES_ROOT, locale, slug, "index.mdx");
      if (existsSync(outMdx)) {
        console.log(`[translate] skip services/${locale}/${slug} (already exists)`);
        continue;
      }
      if (args.dryRun) {
        console.log(`[dry-run] services/${locale}/${slug}`);
        written++;
        continue;
      }
      console.log(`[translate] services/${locale}/${slug}…`);
      try {
        const final = await translateFileWithRetry(source, locale, "service", `services/${locale}/${slug}`);
        await mkdir(dirname(outMdx), { recursive: true });
        await writeFile(outMdx, final, "utf8");
        await copyDirAssets(join(SERVICES_ROOT, slug), join(SERVICES_ROOT, locale, slug));
        // Translate the per-service carousel caption files too
        for (const carouselFile of carouselFiles) {
          const rel = carouselFile.slice(join(SERVICES_ROOT, slug).length + 1);
          const outCarousel = join(SERVICES_ROOT, locale, slug, rel);
          const carouselSource = await readFile(carouselFile, "utf8");
          const carouselFinal = await translateFileWithRetry(
            carouselSource,
            locale,
            "section",
            `services/${locale}/${slug}/${rel}`,
          );
          await mkdir(dirname(outCarousel), { recursive: true });
          await writeFile(outCarousel, carouselFinal, "utf8");
          written++;
        }
        written++;
      } catch (err) {
        console.warn(`[translate] services/${locale}/${slug} failed, skipping (will retry next run):`, (err as Error).message);
      }
    }
  }
  return written;
}

async function translateSections(args: Args): Promise<number> {
  const sections = await listDirs(SECTIONS_ROOT);
  let written = 0;
  for (const section of sections) {
    const sectionDir = join(SECTIONS_ROOT, section);
    const mdFiles = await listMdFiles(sectionDir);
    for (const file of mdFiles) {
      const relative = file.slice(sectionDir.length + 1);
      const source = await readFile(file, "utf8");
      for (const locale of args.locales) {
        const outFile = join(sectionDir, locale, relative);
        if (existsSync(outFile)) {
          console.log(`[translate] skip sections/${section}/${locale}/${relative} (already exists)`);
          continue;
        }
        if (args.dryRun) {
          console.log(`[dry-run] sections/${section}/${locale}/${relative}`);
          written++;
          continue;
        }
        console.log(`[translate] sections/${section}/${locale}/${relative}…`);
        try {
          const final = await translateFileWithRetry(source, locale, "section", `sections/${section}/${locale}/${relative}`);
          await mkdir(dirname(outFile), { recursive: true });
          await writeFile(outFile, final, "utf8");
          await copySectionAssets(sectionDir, join(sectionDir, locale));
          written++;
        } catch (err) {
          console.warn(`[translate] sections/${section}/${locale}/${relative} failed, skipping (will retry next run):`, (err as Error).message);
        }
      }
    }
  }
  return written;
}

async function copySectionAssets(sectionDir: string, localeDir: string): Promise<void> {
  const entries = await readdir(sectionDir, { withFileTypes: true });
  for (const e of entries) {
    if (LOCALE_DIRS.has(e.name) || e.isDirectory()) continue;
    if (/\.(md|mdx)$/i.test(e.name)) continue;
    await copyFile(join(sectionDir, e.name), join(localeDir, e.name));
  }
}

interface CityTranslation {
  province?: { title?: string; type?: string };
  cities?: Record<string, { title?: string; description?: string; description2?: string; type?: string; faq?: { q: string; a: string }[] }>;
}

function cityFields(source: any): string {
  const out: any = { slug: source.slug, name: source.name };
  for (const key of ["title", "description", "description2", "type"]) {
    if (source[key]) out[key] = source[key];
  }
  if (Array.isArray(source.faq) && source.faq.length > 0) {
    out.faq = source.faq.map((f: any) => ({ q: f.q, a: f.a }));
  }
  return JSON.stringify(out, null, 2);
}

async function loadLocalizedJson(locale: string): Promise<Record<string, CityTranslation>> {
  const path = join(LOCALIZED_CITIES_ROOT, `${locale}.json`);
  if (!existsSync(path)) return {};
  try {
    return JSON.parse(await readFile(path, "utf8")) as Record<string, CityTranslation>;
  } catch {
    return {};
  }
}

async function saveLocalizedJson(locale: string, data: Record<string, CityTranslation>): Promise<void> {
  const path = join(LOCALIZED_CITIES_ROOT, `${locale}.json`);
  const tmp = `${path}.tmp`;
  await mkdir(LOCALIZED_CITIES_ROOT, { recursive: true });
  await writeFile(tmp, JSON.stringify(data, null, 2), "utf8");
  await rename(tmp, path);
}

async function translateCitiesBatch(
  locale: string,
  provinceSlug: string,
  batch: any[],
): Promise<any> {
  const payload: any = { province: { slug: provinceSlug }, cities: batch.map(cityFields) };
  return JSON.parse(await translateJsonContent(JSON.stringify(payload), locale));
}

async function translateCities(args: Args): Promise<number> {
  let translated = 0;
  for (const locale of args.locales) {
    const data = await loadLocalizedJson(locale);
    for (const [provinceSlug, province] of Object.entries(provincesMetadata as any)) {
      const cities = ((allSpainCities as any)[provinceSlug] || []) as any[];
      if (cities.length === 0) continue;
      // Skip if the whole province is already translated
      const done = data[provinceSlug]?.cities ?? {};
      const missing = cities.filter((c) => !done[c.slug]);
      if (missing.length === 0 && data[provinceSlug]?.province?.title) {
        console.log(`[translate] skip cities/${locale}/${provinceSlug} (already exists)`);
        continue;
      }
      if (args.dryRun) {
        console.log(`[dry-run] cities/${locale}/${provinceSlug} (${missing.length} cities)`);
        translated += missing.length;
        continue;
      }
      const entry: CityTranslation = data[provinceSlug] ?? {};
      // Fill missing province meta (title/type) with a meta-only call
      if ((!entry.province?.title || !entry.province?.type) && missing.length === 0) {
        console.log(`[translate] cities/${locale}/${provinceSlug} province meta only…`);
        try {
          const payload = { province: { slug: provinceSlug, title: (province as any).title, type: (province as any).type }, cities: [] };
          const result = JSON.parse(await translateJsonContent(JSON.stringify(payload), locale)) as any;
          const meta = result.province ?? {};
          if (!meta.title || !meta.type || meta.title === provinceSlug) {
            throw new Error('empty or echoed province meta in response');
          }
          entry.province = meta;
          data[provinceSlug] = entry;
          await saveLocalizedJson(locale, data);
          translated++;
        } catch (err) {
          console.warn(`[translate] cities/${locale}/${provinceSlug} province meta failed:`, (err as Error).message);
        }
        continue;
      }
      // City batches of CITIES_BATCH_SIZE; the first batch also carries the province meta translation
      for (let i = 0; i < missing.length; i += CITIES_BATCH_SIZE) {
        const batch = missing.slice(i, i + CITIES_BATCH_SIZE);
        console.log(`[translate] cities/${locale}/${provinceSlug} batch ${i / CITIES_BATCH_SIZE + 1} (${batch.length} cities)…`);
        try {
          const result = (await translateCitiesBatch(locale, provinceSlug, batch)) as any;
          if (!entry.province) entry.province = result.province ?? {};
          entry.cities = { ...(entry.cities ?? {}), ...(result.cities ?? {}) };
          translated += Object.keys(result.cities ?? {}).length;
        } catch (err) {
          console.warn(`[translate] cities/${locale}/${provinceSlug} batch failed:`, (err as Error).message);
          break;
        }
      }
      data[provinceSlug] = entry;
      await saveLocalizedJson(locale, data);
    }
  }
  return translated;
}

async function main(): Promise<void> {
  const args = parseArgs(process.argv.slice(2));
  console.log(
    `[backfill] targets=${[...args.targets].join(",")} locales=${args.locales.join(",")}${args.dryRun ? " (dry-run)" : ""}`,
  );

  let total = 0;
  if (args.targets.has("blog")) total += await translateBlog(args);
  if (args.targets.has("services")) total += await translateServices(args);
  if (args.targets.has("sections")) total += await translateSections(args);
  if (args.targets.has("cities")) total += await translateCities(args);
  console.log(`[backfill] done. Files written: ${total}`);
}

main().catch((err) => {
  console.error("[backfill] failed:", err);
  process.exit(1);
});