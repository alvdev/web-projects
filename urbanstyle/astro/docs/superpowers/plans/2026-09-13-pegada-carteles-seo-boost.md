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

---

## Phase G — Learn-more cards + localized homepage parity (2026-09-14)

> **Status:** not started. Follow-up to Phase D: commit `661f9f6d` added the new homepage sections to the ES page only, and commit `a2f83964` created the localized copies without them.

**Goal:** Replace the plain link list in the homepage "Aprende más sobre publicidad exterior" section with image cards matching the /blog post-picture style (absolute title over the image), and bring the en/fr/it/pt homepages to parity with ES (what's included, formats, authorized areas, cities, learn more).

**Architecture:** New `src/components/BlogPostCards.astro` queries the `posts` collection for the current locale and matches by slug, so localized covers/titles come from each locale's post frontmatter — no duplicated copy. Section copy is inlined in each localized `src/content/services/<locale>/pegada-de-carteles/index.mdx`, same pattern as ES. No test framework: `bun run build` (`astro check && astro build`) + dist grep assertions.

### Task G1 — BlogPostCards component

**Files:**
- Create: `src/components/BlogPostCards.astro`
- Modify: `src/content/services/pegada-de-carteles/index.mdx` (add import; replace the `<section>` at lines 139–146)

- [ ] Create `src/components/BlogPostCards.astro` (card wrapper/image classes copied from `src/pages/blog/index.astro:53-61`; title overlay pattern from `src/components/CloudServices.astro:23`):

```astro
---
import { Image } from "astro:assets";
import { getCollection } from "astro:content";
import postCoverPlaceholder from "@/assets/images/header/post-cover-placeholder.webp";
import { entryMatchesLocale, type Locale } from "@/i18n/ui";

interface Props {
    title: string;
    slugs: string[];
    classes?: string;
}

const { title, slugs, classes = "" } = Astro.props;
const locale = (Astro.currentLocale ?? "es") as Locale;
const blogUrl = import.meta.env.BASE_URL + (locale === "es" ? "" : `${locale}/`) + "blog/";

const posts = (await getCollection("posts")).filter(entry => entryMatchesLocale(entry, locale));
const bySlug = new Map(posts.map(post => [post.id.split("/").pop()!, post] as const));

const cards = [];
for (const slug of slugs) {
    const post = bySlug.get(slug);
    if (post) cards.push(post);
}
---

<section class={`container mt-32 ${classes}`}>
    <h2 class="text-4xl font-extrabold uppercase text-black md:text-5xl">{title}</h2>
    <div class="mt-12 grid gap-10 md:grid-cols-3">
        {cards.map(post => {
            const slug = post.id.split("/").pop()!;
            return (
                <article class="bg-skew">
                    <a href={blogUrl + slug} class="group relative block">
                        <Image
                            src={post.data.cover.url ?? postCoverPlaceholder}
                            alt={post.data.cover.alt || post.data.title}
                            class="m-0 p-2 border border-black bg-white aspect-[16/12] object-cover"
                            width={700}
                            height={400}
                        />
                        <h3 class="absolute bottom-2 left-2 z-10 m-0 w-[calc(100%-16px)] px-3 pt-16 pb-2 text-lg font-bold leading-tight text-white text-shadow-xs text-shadow-black bg-linear-to-t from-black/85 via-black/45 to-transparent overflow-clip">
                            {post.data.title}
                        </h3>
                    </a>
                </article>
            );
        })}
    </div>
</section>
```

- [ ] In the ES MDX add `import BlogPostCards from '@/components/BlogPostCards.astro';` and replace the section with:

```astro
<BlogPostCards
  title="Aprende más sobre publicidad exterior"
  slugs={[
    "es-legal-pegar-carteles-en-la-calle",
    "la-importancia-del-diseno-en-la-pegada-de-carteles",
    "como-usar-de-manera-correcta-una-campana-de-wild-posting-y-pegada-de-carteles",
  ]}
/>
```

- [ ] `bun run build` → no errors; `grep -oF 'aspect-[16/12]' dist/index.html | wc -l` → 3

### Task G2 — Localized homepage parity

**Files:** modify `src/content/services/{en,fr,it,pt}/pegada-de-carteles/index.mdx` — add the `BlogPostCards` import and insert the blocks below after `{<BenefitsGrid classes="relative z-20 -mt-28" />}`, before `<Faqs />`.

- [ ] **EN** (`src/content/services/en/pegada-de-carteles/index.mdx`):

```astro
<section class="container mt-32">
  <h2 class="text-center text-5xl font-extrabold text-balance text-black uppercase md:text-7xl">
    What our
    <br /> poster pasting includes
  </h2>
  <p class="font-script mt-8 text-center text-4xl font-bold text-balance md:text-5xl">A complete service, from printing to <mark class="under">replacement</mark></p>

  <div class="grid gap-4 mt-16 md:grid-cols-2 lg:grid-cols-3">
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Route planning</h3>
      <p class="mt-4">We analyze your audience and design a circuit through high-traffic areas: shopping streets, transport hubs, university areas and nightlife venues.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Design and printing</h3>
      <p class="mt-4">We print in every format, from A3 to large format. If you need it, our team designs the poster tailored to your campaign.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Our own team of pasters</h3>
      <p class="mt-4">We have local teams in Madrid, Barcelona and the main capitals, with coverage across the whole country.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">24/7 supervision</h3>
      <p class="mt-4">We check the locations day and night throughout the campaign so that your poster keeps being seen where you booked it.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Guaranteed replacement</h3>
      <p class="mt-4">If a poster is damaged, torn down or covered, we replace it. The replacement guarantee is included during the display period.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Photo report</h3>
      <p class="mt-4">We document every location with photos at the start, during and at the end of the campaign, so you can verify the work carried out.</p>
    </div>
  </div>
</section>

<section class="container mt-32 grid gap-16 lg:grid-cols-2">
  <div>
    <h2 class="text-4xl font-extrabold uppercase text-black md:text-5xl">Formats and media</h2>
    <p class="mt-6 text-xl text-gray-600">We paste posters in all the usual outdoor advertising formats and adapt the medium to each campaign:</p>
    <ul class="mt-6 space-y-2 text-lg">
      <li>A3, A2 and B2 posters for shops, bars and indoor spaces.</li>
      <li>Large format B1 and B0 on advertising columns and authorized walls.</li>
      <li>Street furniture: bus shelters, bus stops and panels.</li>
      <li>Indoor circuits in universities, nightlife venues and shops.</li>
    </ul>
  </div>
  <div>
    <h2 class="text-4xl font-extrabold uppercase text-black md:text-5xl">Authorized areas and regulations</h2>
    <p class="mt-6 text-xl text-gray-600">We work on authorized spaces and respect each municipality's local ordinances. Our team knows the enabled areas and each council's requirements, so your campaign runs without surprises and with maximum visibility.</p>
    <a href="/en/contacto/" class="btn mt-8">Request a quote</a>
  </div>
</section>

<section class="container mt-32 text-center">
  <h2 class="text-5xl font-extrabold uppercase text-black md:text-7xl">Poster pasting by city</h2>
  <p class="font-script mt-8 text-4xl font-bold text-balance md:text-5xl">We have a direct presence and local teams in the main capitals</p>
  <ul class="mt-12 flex flex-wrap justify-center gap-4 list-none">
    <li><a href="/en/poster-pasting/madrid/" class="btn">Poster pasting in Madrid</a></li>
    <li><a href="/en/poster-pasting/barcelona/" class="btn">Poster pasting in Barcelona</a></li>
    <li><a href="/en/poster-pasting/valencia/" class="btn">Poster pasting in Valencia</a></li>
    <li><a href="/en/poster-pasting/sevilla/" class="btn">Poster pasting in Seville</a></li>
    <li><a href="/en/poster-pasting/malaga/" class="btn">Poster pasting in Malaga</a></li>
  </ul>
  <p class="mt-10 text-xl text-gray-600">Don't see your city? Check our <a href="/en/poster-pasting/">poster pasting coverage across Spain</a>.</p>
</section>

<BlogPostCards
  title="Learn more about outdoor advertising"
  slugs={[
    "es-legal-pegar-carteles-en-la-calle",
    "la-importancia-del-diseno-en-la-pegada-de-carteles",
    "como-usar-de-manera-correcta-una-campana-de-wild-posting-y-pegada-de-carteles",
  ]}
/>
```

- [ ] **FR** (`src/content/services/fr/pegada-de-carteles/index.mdx`):

```astro
<section class="container mt-32">
  <h2 class="text-center text-5xl font-extrabold text-balance text-black uppercase md:text-7xl">
    Ce que comprend notre
    <br /> collage d'affiches
  </h2>
  <p class="font-script mt-8 text-center text-4xl font-bold text-balance md:text-5xl">Un service complet, de l'impression au <mark class="under">remplacement</mark></p>

  <div class="grid gap-4 mt-16 md:grid-cols-2 lg:grid-cols-3">
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Planification des tournées</h3>
      <p class="mt-4">Nous analysons votre public et concevons un circuit dans les zones à fort passage : rues commerçantes, pôles d'échange, quartiers universitaires et lieux de sortie.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Design et impression</h3>
      <p class="mt-4">Nous imprimons dans tous les formats, du A3 au grand format. Si vous en avez besoin, notre équipe conçoit l'affiche adaptée à votre campagne.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Équipe de colleurs dédiée</h3>
      <p class="mt-4">Nous disposons d'équipes locales à Madrid, Barcelone et dans les principales capitales, avec une couverture sur tout le territoire national.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Supervision 24/7</h3>
      <p class="mt-4">Nous vérifions les emplacements jour et nuit pendant toute la campagne pour que votre affiche reste visible là où vous l'avez réservée.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Remplacement garanti</h3>
      <p class="mt-4">Si une affiche est endommagée, arrachée ou recouverte, nous la remplaçons. La garantie de remplacement est incluse pendant la période d'affichage.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Rapport photo</h3>
      <p class="mt-4">Nous documentons chaque emplacement avec des photos au début, pendant et à la fin de la campagne, pour que vous puissiez vérifier le travail réalisé.</p>
    </div>
  </div>
</section>

<section class="container mt-32 grid gap-16 lg:grid-cols-2">
  <div>
    <h2 class="text-4xl font-extrabold uppercase text-black md:text-5xl">Formats et supports</h2>
    <p class="mt-6 text-xl text-gray-600">Nous collons des affiches dans tous les formats habituels de la publicité extérieure et adaptons le support à chaque campagne :</p>
    <ul class="mt-6 space-y-2 text-lg">
      <li>Affiches A3, A2 et B2 pour commerces, bars et espaces intérieurs.</li>
      <li>Grand format B1 et B0 sur colonnes d'affichage et murs autorisés.</li>
      <li>Mobilier urbain : abris-bus, arrêts de bus et panneaux.</li>
      <li>Circuits intérieurs dans les universités, les lieux de sortie et les boutiques.</li>
    </ul>
  </div>
  <div>
    <h2 class="text-4xl font-extrabold uppercase text-black md:text-5xl">Zones autorisées et réglementation</h2>
    <p class="mt-6 text-xl text-gray-600">Nous travaillons sur des espaces autorisés et respectons les règlements municipaux de chaque commune. Notre équipe connaît les zones habilitées et les exigences de chaque mairie, pour que votre campagne se déroule sans surprise et avec une visibilité maximale.</p>
    <a href="/fr/contacto/" class="btn mt-8">Demander un devis</a>
  </div>
</section>

<section class="container mt-32 text-center">
  <h2 class="text-5xl font-extrabold uppercase text-black md:text-7xl">Collage d'affiches par ville</h2>
  <p class="font-script mt-8 text-4xl font-bold text-balance md:text-5xl">Nous avons une présence directe et des équipes locales dans les principales capitales</p>
  <ul class="mt-12 flex flex-wrap justify-center gap-4 list-none">
    <li><a href="/fr/collage-affiches/madrid/" class="btn">Collage d'affiches à Madrid</a></li>
    <li><a href="/fr/collage-affiches/barcelona/" class="btn">Collage d'affiches à Barcelone</a></li>
    <li><a href="/fr/collage-affiches/valencia/" class="btn">Collage d'affiches à Valence</a></li>
    <li><a href="/fr/collage-affiches/sevilla/" class="btn">Collage d'affiches à Séville</a></li>
    <li><a href="/fr/collage-affiches/malaga/" class="btn">Collage d'affiches à Malaga</a></li>
  </ul>
  <p class="mt-10 text-xl text-gray-600">Votre ville n'apparaît pas ? Consultez notre <a href="/fr/collage-affiches/">couverture du collage d'affiches en Espagne</a>.</p>
</section>

<BlogPostCards
  title="En savoir plus sur la publicité extérieure"
  slugs={[
    "es-legal-pegar-carteles-en-la-calle",
    "la-importancia-del-diseno-en-la-pegada-de-carteles",
    "como-usar-de-manera-correcta-una-campana-de-wild-posting-y-pegada-de-carteles",
  ]}
/>
```

- [ ] **IT** (`src/content/services/it/pegada-de-carteles/index.mdx`):

```astro
<section class="container mt-32">
  <h2 class="text-center text-5xl font-extrabold text-balance text-black uppercase md:text-7xl">
    Cosa comprende la nostra
    <br /> affissione di manifesti
  </h2>
  <p class="font-script mt-8 text-center text-4xl font-bold text-balance md:text-5xl">Un servizio completo, dalla stampa alla <mark class="under">sostituzione</mark></p>

  <div class="grid gap-4 mt-16 md:grid-cols-2 lg:grid-cols-3">
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Pianificazione dei percorsi</h3>
      <p class="mt-4">Analizziamo il tuo pubblico e progettiamo un circuito nelle zone ad alto passaggio: vie commerciali, nodi di scambio, quartieri universitari e locali notturni.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Design e stampa</h3>
      <p class="mt-4">Stampiamo in tutti i formati, dall'A3 al grande formato. Se ne hai bisogno, il nostro team progetta il manifesto adatto alla tua campagna.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Squadra di affissori propria</h3>
      <p class="mt-4">Disponiamo di squadre locali a Madrid, Barcellona e nelle principali capitali, con copertura su tutto il territorio nazionale.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Supervisione 24/7</h3>
      <p class="mt-4">Controlliamo le posizioni giorno e notte per tutta la campagna, così il tuo manifesto continua a essere visibile dove l'hai prenotato.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Sostituzione garantita</h3>
      <p class="mt-4">Se un manifesto viene danneggiato, strappato o coperto, lo sostituiamo. La garanzia di sostituzione è inclusa durante il periodo di esposizione.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Report fotografico</h3>
      <p class="mt-4">Documentiamo ogni posizione con foto all'inizio, durante e alla fine della campagna, così puoi verificare il lavoro svolto.</p>
    </div>
  </div>
</section>

<section class="container mt-32 grid gap-16 lg:grid-cols-2">
  <div>
    <h2 class="text-4xl font-extrabold uppercase text-black md:text-5xl">Formati e supporti</h2>
    <p class="mt-6 text-xl text-gray-600">Affiggiamo manifesti in tutti i formati abituali della pubblicità esterna e adattiamo il supporto a ogni campagna:</p>
    <ul class="mt-6 space-y-2 text-lg">
      <li>Manifesti A3, A2 e B2 per negozi, bar e spazi interni.</li>
      <li>Grande formato B1 e B0 su colonne pubblicitarie e muri autorizzati.</li>
      <li>Arredo urbano: pensiline, fermate dell'autobus e pannelli.</li>
      <li>Circuiti interni in università, locali notturni e negozi.</li>
    </ul>
  </div>
  <div>
    <h2 class="text-4xl font-extrabold uppercase text-black md:text-5xl">Zone autorizzate e normativa</h2>
    <p class="mt-6 text-xl text-gray-600">Lavoriamo su spazi autorizzati e rispettiamo i regolamenti comunali di ogni località. Il nostro team conosce le zone abilitate e i requisiti di ogni comune, così la tua campagna si svolge senza sorprese e con la massima visibilità.</p>
    <a href="/it/contacto/" class="btn mt-8">Richiedi un preventivo</a>
  </div>
</section>

<section class="container mt-32 text-center">
  <h2 class="text-5xl font-extrabold uppercase text-black md:text-7xl">Affissione di manifesti per città</h2>
  <p class="font-script mt-8 text-4xl font-bold text-balance md:text-5xl">Abbiamo una presenza diretta e squadre locali nelle principali capitali</p>
  <ul class="mt-12 flex flex-wrap justify-center gap-4 list-none">
    <li><a href="/it/affissione-manifesti/madrid/" class="btn">Affissione di manifesti a Madrid</a></li>
    <li><a href="/it/affissione-manifesti/barcelona/" class="btn">Affissione di manifesti a Barcellona</a></li>
    <li><a href="/it/affissione-manifesti/valencia/" class="btn">Affissione di manifesti a Valencia</a></li>
    <li><a href="/it/affissione-manifesti/sevilla/" class="btn">Affissione di manifesti a Siviglia</a></li>
    <li><a href="/it/affissione-manifesti/malaga/" class="btn">Affissione di manifesti a Malaga</a></li>
  </ul>
  <p class="mt-10 text-xl text-gray-600">Non vedi la tua città? Consulta la nostra <a href="/it/affissione-manifesti/">copertura di affissione manifesti in Spagna</a>.</p>
</section>

<BlogPostCards
  title="Scopri di più sulla pubblicità esterna"
  slugs={[
    "es-legal-pegar-carteles-en-la-calle",
    "la-importancia-del-diseno-en-la-pegada-de-carteles",
    "como-usar-de-manera-correcta-una-campana-de-wild-posting-y-pegada-de-carteles",
  ]}
/>
```

- [ ] **PT** (`src/content/services/pt/pegada-de-carteles/index.mdx`):

```astro
<section class="container mt-32">
  <h2 class="text-center text-5xl font-extrabold text-balance text-black uppercase md:text-7xl">
    O que inclui a nossa
    <br /> colagem de cartazes
  </h2>
  <p class="font-script mt-8 text-center text-4xl font-bold text-balance md:text-5xl">Um serviço completo, da impressão à <mark class="under">reposição</mark></p>

  <div class="grid gap-4 mt-16 md:grid-cols-2 lg:grid-cols-3">
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Planeamento de rotas</h3>
      <p class="mt-4">Analisamos o teu público e desenhamos um circuito por zonas de grande movimento: ruas comerciais, interfaces de transporte, zonas universitárias e locais de diversão noturna.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Design e impressão</h3>
      <p class="mt-4">Imprimimos em todos os formatos, do A3 ao grande formato. Se precisares, a nossa equipa desenha o cartaz adaptado à tua campanha.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Equipa própria de coladores</h3>
      <p class="mt-4">Contamos com equipas locais em Madrid, Barcelona e nas principais capitais, com cobertura em todo o território nacional.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Supervisão 24/7</h3>
      <p class="mt-4">Verificamos as localizações dia e noite durante toda a campanha para que o teu cartaz continue a ser visto onde o contrataste.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Reposição garantida</h3>
      <p class="mt-4">Se um cartaz aparecer danificado, arrancado ou tapado, repomo-lo. A garantia de reposição está incluída durante o período de exibição.</p>
    </div>
    <div class="p-8 bg-white border rounded-xs shadow-xl">
      <h3 class="text-2xl font-semibold">Relatório fotográfico</h3>
      <p class="mt-4">Documentamos cada localização com fotos no início, durante e no final da campanha, para que verifiques o trabalho realizado.</p>
    </div>
  </div>
</section>

<section class="container mt-32 grid gap-16 lg:grid-cols-2">
  <div>
    <h2 class="text-4xl font-extrabold uppercase text-black md:text-5xl">Formatos e suportes</h2>
    <p class="mt-6 text-xl text-gray-600">Colamos cartazes em todos os formatos habituais da publicidade exterior e adaptamos o suporte a cada campanha:</p>
    <ul class="mt-6 space-y-2 text-lg">
      <li>Cartazes A3, A2 e B2 para lojas, bares e espaços interiores.</li>
      <li>Grande formato B1 e B0 em colunas publicitárias e muros autorizados.</li>
      <li>Mobiliário urbano: abrigos, paragens de autocarro e painéis.</li>
      <li>Circuitos interiores em universidades, locais de diversão noturna e lojas.</li>
    </ul>
  </div>
  <div>
    <h2 class="text-4xl font-extrabold uppercase text-black md:text-5xl">Zonas autorizadas e regulamentação</h2>
    <p class="mt-6 text-xl text-gray-600">Trabalhamos em espaços autorizados e respeitamos os regulamentos municipais de cada localidade. A nossa equipa conhece as zonas autorizadas e os requisitos de cada município, para que a tua campanha decorra sem surpresas e com a máxima visibilidade.</p>
    <a href="/pt/contacto/" class="btn mt-8">Pedir orçamento</a>
  </div>
</section>

<section class="container mt-32 text-center">
  <h2 class="text-5xl font-extrabold uppercase text-black md:text-7xl">Colagem de cartazes por cidade</h2>
  <p class="font-script mt-8 text-4xl font-bold text-balance md:text-5xl">Temos presença direta e equipas locais nas principais capitais</p>
  <ul class="mt-12 flex flex-wrap justify-center gap-4 list-none">
    <li><a href="/pt/colagem-cartazes/madrid/" class="btn">Colagem de cartazes em Madrid</a></li>
    <li><a href="/pt/colagem-cartazes/barcelona/" class="btn">Colagem de cartazes em Barcelona</a></li>
    <li><a href="/pt/colagem-cartazes/valencia/" class="btn">Colagem de cartazes em Valência</a></li>
    <li><a href="/pt/colagem-cartazes/sevilla/" class="btn">Colagem de cartazes em Sevilha</a></li>
    <li><a href="/pt/colagem-cartazes/malaga/" class="btn">Colagem de cartazes em Málaga</a></li>
  </ul>
  <p class="mt-10 text-xl text-gray-600">Não vês a tua cidade? Consulta a nossa <a href="/pt/colagem-cartazes/">cobertura de colagem de cartazes em Espanha</a>.</p>
</section>

<BlogPostCards
  title="Aprende mais sobre publicidade exterior"
  slugs={[
    "es-legal-pegar-carteles-en-la-calle",
    "la-importancia-del-diseno-en-la-pegada-de-carteles",
    "como-usar-de-manera-correcta-una-campana-de-wild-posting-y-pegada-de-carteles",
  ]}
/>
```

### Task G3 — Build + verification

- [ ] `bun run build` → `astro check` clean, all pages built
- [ ] `grep -oF 'aspect-[16/12]' dist/index.html | wc -l` → 3; repeat for `dist/en/index.html`, `dist/fr/index.html`, `dist/it/index.html`, `dist/pt/index.html`
- [ ] Headings present: `Aprende más sobre publicidad exterior` (ES), `Learn more about outdoor advertising` (EN), `En savoir plus sur la publicité extérieure` (FR), `Scopri di più sulla pubblicità esterna` (IT), `Aprende mais sobre publicidade exterior` (PT)
- [ ] Card links present and localized: `/blog/...` (ES), `/en/blog/...`, `/fr/blog/...`, `/it/blog/...`, `/pt/blog/...`
- [ ] Section parity: `What our poster pasting includes` / `Formats and media` / `Authorized areas and regulations` / `Poster pasting by city` on `dist/en/index.html`; equivalents on fr/it/pt
- [ ] Locale CTAs resolve: `/en/contacto/`, `/fr/contacto/`, `/it/contacto/`, `/pt/contacto/`; coverage links `/en/poster-pasting/madrid/`, `/fr/collage-affiches/madrid/`, `/it/affissione-manifesti/madrid/`, `/pt/colagem-cartazes/madrid/`
- [ ] Commit: `feat(seo): blog cards for learn-more section + localized homepage sections`
