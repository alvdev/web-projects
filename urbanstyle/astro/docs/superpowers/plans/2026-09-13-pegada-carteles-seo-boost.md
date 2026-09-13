# Pegada de carteles SEO Boost — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans or superpowers:subagent-driven-development. Steps use checkbox (`- [ ]`) syntax.

**Goal:** Restore/defend homepage #3 position for "pegada de carteles" by fixing the multilingual hreflang defect, consolidating head-term signals on `/`, strengthening homepage content, and adding GSC measurement.

**Architecture:** Static Astro 5 site (5 locales, prefixDefaultLocale=false) deployed over FTP by the instagram-to-blog pipeline. No test framework — `bun run build` + dist assertion scripts are the verification.

**Tech Stack:** Astro 5.17, @astrojs/sitemap 3.7, astro-seo, Bun, @googleapis/searchconsole.

**Context:** 2026-09-10 i18n rollout (751 files, 1127 sitemap URLs) shipped without in-HTML hreflang and with sitemap alternates missing on 858 localized coverage/city URLs. Homepage slid #3 → #4 in the following days. Blog pipeline remains running (user decision).

---

## Task 0 — GSC baseline

**Files:**
- Create: `scripts/seo/gsc-report.ts`
- Modify: `.env` (gitignored) — add `GSC_SITE_URL`

- [ ] Add `googleapis` dependency (`bun add googleapis`)
- [ ] Script lists accessible properties, pulls `searchanalytics.query` for `[query, page]` and `[date]` rows, writes CSV to `seo/reports/`
- [ ] Pull baseline: 16 months for query `pegada de carteles` + unfiltered queries/pages, compare 2026-09-01..09-09 vs 09-10..09-13
- [ ] Record findings: position trend, impressions vs position, ranking URL, index coverage, manual actions

## Phase A — Fix i18n hreflang defect (highest priority)

### Task A1 — Extract i18n constants
**Files:** Create `src/i18n/config.ts`; modify `src/i18n/ui.ts`
- [ ] Move `languages`, `Locale`, `defaultLocale`, `localePrefixes`, `coverageSlugs`, `ogLocales` into `config.ts`
- [ ] `ui.ts` imports from `./config` and re-exports for backward compatibility
- [ ] `bun run build` still passes

### Task A2 — Hreflang helper
**Files:** Create `src/i18n/hreflang.ts`
- [ ] `localeFromPathname(pathname)` detects locale from prefix
- [ ] `hreflangAlternates(pathname, site, onlyLocales?)` returns `[{lang, url}]` + `x-default`
- [ ] Maps localized coverage slugs (`poster-pasting` ↔ `pegada-carteles`, etc.)
- [ ] `/legal/*` returns ES + x-default only

### Task A3 — Emit hreflang in every page
**Files:** Modify `src/layouts/Base.astro`; create `src/i18n/content.ts`; modify 5 × `src/pages/**/blog/[...post].astro`
- [ ] Base accepts `hreflangLocales?: Locale[]`, emits `<link rel="alternate" hreflang>` for each alternate
- [ ] `getPostLocaleVariants(slug)` returns locales that actually have the post
- [ ] Blog templates compute variants and pass to Base
- [ ] Build + grep dist HTML: hreflang present and reciprocal

### Task A4 — Fix sitemap alternates
**Files:** Modify `astro.config.ts`
- [ ] Remove `i18n` option from `sitemap(...)`
- [ ] Add `serialize(item)` that sets `item.links = hreflangAlternates(new URL(item.url).pathname, new URL(item.url))`
- [ ] Assert: ~1127 sitemap URLs with alternates; `/en/poster-pasting/madrid/` ↔ `/pegada-carteles/madrid/`

### Task A5 — Verify script
**Files:** Create `scripts/seo/verify-hreflang.ts`
- [ ] Walk `dist/**/*.html`, extract alternates, assert targets exist and are reciprocal
- [ ] Fail non-zero on error

### Task A6 — xpecado URL break
**Files:** Rename dirs in 5 locales; create `public/.htaccess`
- [ ] `git mv src/content/blog[/{en,fr,it,pt}]/accion-espececial-xpecado` → `accion-especial-xpecado-madrid`
- [ ] `.htaccess` 301 from old typo URL (all locale prefixes)
- [ ] Note: blog routes use directory name, frontmatter `slug` is dead field

## Phase B — Blog technical quality

### Task B1 — BlogPosting schema
**Files:** Modify `src/layouts/Base.astro`, `src/components/Schemas.astro`, 5 blog templates
- [ ] Base forwards `datePublished`, `dateModified`, `pageUrl`, `locale`, `keywords`
- [ ] Fix dead `blogPosting` schema: slug-based `@id`, real dates, `inLanguage` map, real keywords
- [ ] Blog templates pass `schema="blogposting"` + parsed pubDate
- [ ] Validate JSON-LD in built post

### Task B2 — Trailing-slash consistency
**Files:** `[...service]/pegada-carteles/index.astro`, 5 blog templates
- [ ] City internal links end with `/` to match canonicals

## Phase C — Consolidate head-term signals on `/`

### Task C1 — Diversify banner anchor
**Files:** `src/i18n/ui.ts`
- [ ] `posterPasting.link` → coverage-descriptive anchor in 5 locales

### Task C2 — Homepage CTA in posts
**Files:** Create `src/components/banners/ServiceCta.astro`; modify 5 blog templates; `src/i18n/ui.ts`
- [ ] Component links to localized homepage with "servicio de pegada de carteles" anchor
- [ ] Inserted after `<Content />` in all post templates

## Phase D — Homepage depth & authority

### Task D1 — Expand homepage service content
**Files:** `src/content/services/pegada-de-carteles/index.mdx`
- [ ] H2 sections: qué incluye, formatos/soportes, pegada por ciudades (internal links), normativa
- [ ] ≥5 new internal links with descriptive anchors

### Task D2 — Homepage → top content links
**Files:** `src/content/services/pegada-de-carteles/index.mdx`
- [ ] Link 2–3 strongest informational posts

## Phase E — Pipeline guardrails (keep running)

### Task E1 — Real dateModified
**Files:** `scripts/instagram-to-blog/content.ts`, `src/content.config.ts`, blog templates
- [ ] Generated posts carry `updatedDate` (actual publish time)
- [ ] Schema uses it as `dateModified`

### Task E2 — Translation QA
- [ ] Fix known typos (FR "funciona", EN terminology)
- [ ] Add QA instruction to translation prompt

## Phase F — Measurement

- [ ] Weekly GSC report; success = homepage ≤3 head query (Madrid UULE, pws=0) in 4–6 weeks
- [ ] 100% sitemap hreflang coverage; zero 404s on renamed URLs

---

## Results (2026-09-13)

**Commits**
- `894fd0d5` seo: GSC reporting + hreflang verification scripts
- `3edc6c53` fix(i18n): hreflang in HTML + sitemap, duplicate localized landings consolidated
- `f5623b78` feat(seo): BlogPosting schema, banner anchor diversification, homepage CTA in posts
- `661f9f6d` content(seo): homepage depth + internal links
- `5d581783` feat(seo): dateModified + translation QA guardrail
- `9467a6c7` fix(i18n): language selector localized coverage slugs

**Verification**
- `bun run build`: 1149 pages, no errors
- `bun scripts/seo/verify-hreflang.ts`: 1148 pages checked, all alternates exist + reciprocal
- `dist/sitemap-0.xml`: 1148/1148 URLs carry hreflang alternates (was 263/1127)
- Blog posts: valid BlogPosting JSON-LD (mainEntityOfPage = canonical, dates, inLanguage, keywords)
- Homepage: 1 H1, new service/format/city/blog sections, city links + blog links present
- Language selector: coverage pages cross-link correctly (`/pegada-carteles/` <-> `/en/poster-pasting/`)

**GSC baseline (Sep 13)**
- Head query "pegada de carteles": homepage avg position 16.7 (16 months), stable ~15.4 -> 15.9 before/after Sep 10; low daily volume (2-21 impressions/day), last 2-3 days incomplete
- No GSC-visible collapse around the Sep 10 i18n deploy; #3 -> #4 is likely tracker/local fluctuation on top of the i18n re-crawl
- 68 pages compete for the head query; homepage is the main ranker (10 clicks / 3998 impressions)
- Reports: `seo/reports/` (gitignored)

**Pending deployment**
- Push to origin/main, then on kv55: `bash update.sh` (pull + bun install for googleapis + bot restart)
- Site rebuild/upload happens with the next pipeline publish; then re-request indexing of key URLs in GSC
