/**
 * Lightweight web search used as a fallback for official-account lookups when
 * grounded Gemini is unavailable (quota/503).
 *
 * Engine order: Bing (organic results are wrapped in `u=a1<base64url>`
 * redirects — decodable, no captcha observed) → DuckDuckGo HTML (`uddg=`
 * params, throttled — it rate-limits aggressive bots with HTTP 202).
 * Google's HTML search is deliberately NOT used: intermittent captcha/consent
 * walls and obfuscated `/url?q=` redirects make it unreliable for bots.
 */

const UA =
  "Mozilla/5.0 (X11; Linux x86_64; rv:150.0) Gecko/20100101 Firefox/150.0";

let lastDdgCall = 0;
const DDG_MIN_INTERVAL_MS = 4_000;

// Circuit breaker: 3 consecutive failures per engine → skip that engine for
// 10 minutes (a success resets the counter). Prevents a throttled engine from
// slowing every lookup when it is down (e.g. DDG 202/timeouts).
const BREAKER_FAIL_LIMIT = 3;
const BREAKER_COOLDOWN_MS = 10 * 60 * 1000;
const engineState: Record<string, { failures: number; cooldownUntil: number }> = {};

function engineAvailable(name: string): boolean {
  const s = engineState[name];
  return !s || Date.now() >= s.cooldownUntil;
}
function engineFail(name: string, err: unknown): void {
  const s = engineState[name] ?? (engineState[name] = { failures: 0, cooldownUntil: 0 });
  s.failures += 1;
  if (s.failures >= BREAKER_FAIL_LIMIT && Date.now() >= s.cooldownUntil) {
    s.cooldownUntil = Date.now() + BREAKER_COOLDOWN_MS;
    console.warn(`[websearch] ${name}: abriendo circuit breaker 10 min (${s.failures} fallos)`);
  }
  console.warn(`[websearch] ${name} failed:`, (err as Error).message);
}
function engineOk(name: string): void {
  if (engineState[name]) engineState[name].failures = 0;
}

/**
 * Build search queries from the Instagram caption at decreasing lengths
 * (60 → 30 → 15 chars). Long caption fragments are too specific for search
 * engines; the short head usually contains the artist/brand name.
 */
export function buildSearchQueries(caption: string): string[] {
  const clean = caption
    .replace(/https?:\/\/\S+/g, "")
    .replace(/#\w+/g, "")
    .replace(/@\w+/g, "")
    .replace(/[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{2B00}-\u{2BFF}\u{FE0F}\u{200D}]/gu, "")
    .replace(/\s+/g, " ")
    .trim();
  const out: string[] = [];
  for (const len of [60, 30, 15]) {
    let frag = clean.slice(0, len).trim();
    // never cut mid-word
    if (frag.length < clean.length) {
      const lastSpace = frag.lastIndexOf(" ");
      if (lastSpace > 0) frag = frag.slice(0, lastSpace).trim();
    }
    if (frag && !out.includes(frag)) out.push(frag);
  }
  return out;
}

/** Shortcut: the primary (60-char) query. */
export function buildSearchQuery(caption: string): string {
  return buildSearchQueries(caption)[0] ?? "";
}

/**
 * Fetch search result URLs (deduplicated, normalized). Engine order:
 * 1) Bing (direct)  2) DuckDuckGo (direct, throttled)  3) Brave HTML through
 * WEBSEARCH_PROXY — ONLY as last fallback, when the direct engines produced no
 * USABLE result for hostFilter (garbage-filled engines count as failures too).
 * hostFilter lets the caller scope what "usable" means (e.g. facebook.com).
 */
export async function fetchSearchResults(
  query: string,
  maxResults = 8,
  hostFilter?: (hostname: string) => boolean,
): Promise<string[]> {
  const urls: string[] = [];
  const seen = new Set<string>();

  const push = (url: string): void => {
    let norm: string;
    try {
      norm = new URL(url).toString().split("#")[0].replace(/\/+$/, "");
    } catch {
      return;
    }
    if (seen.has(norm)) return;
    seen.add(norm);
    urls.push(norm);
  };
  const isUsable = (url: string): boolean => {
    if (!hostFilter) return true;
    try {
      return hostFilter(new URL(url).hostname.toLowerCase());
    } catch {
      return false;
    }
  };

  let directFailed = false;
  let usableCount = 0;

  if (engineAvailable("Bing")) {
    try {
      const bing = await fetchBing(query);
      engineOk("Bing");
      for (const url of bing) {
        push(url);
        if (isUsable(url)) usableCount += 1;
      }
    } catch (err) {
      engineFail("Bing", err);
      directFailed = true;
    }
  }

  if (engineAvailable("DuckDuckGo")) {
    try {
      const ddg = await fetchDuckDuckGo(query);
      engineOk("DuckDuckGo");
      for (const url of ddg) {
        push(url);
        if (isUsable(url)) usableCount += 1;
      }
    } catch (err) {
      engineFail("DuckDuckGo", err);
      directFailed = true;
    }
  }

  // Last fallback: Brave HTML through the proxy, ONLY when the direct engines
  // produced nothing usable (proxy traffic is metered — keep it minimal).
  if ((usableCount === 0 || directFailed) && process.env.WEBSEARCH_PROXY && engineAvailable("BraveProxy")) {
    try {
      const brave = await fetchBraveHtml(query);
      engineOk("BraveProxy");
      for (const url of brave) {
        push(url);
      }
    } catch (err) {
      engineFail("BraveProxy", err);
    }
  }

  // Prioritize USABLE results (hostFilter-passing) so a proxy/fresh engine's
  // good URLs are not drowned out by direct-engine garbage — then cap.
  const usable = urls.filter(isUsable);
  const rest = urls.filter((u) => !isUsable(u));
  return [...usable, ...rest].slice(0, maxResults);
}

/**
 * Brave HTML search through WEBSEARCH_PROXY (residential/datacenter proxy) —
 * the LAST engine. Brave HTML is scrape-friendly and its anchors carry the
 * real target URLs directly.
 */
async function fetchBraveHtml(query: string): Promise<string[]> {
  const html = await fetchBraveHtmlRaw(query);
  const out: string[] = [];
  for (const match of html.matchAll(/href="(https?:\/\/[^"]+)"/g)) {
    let url: string;
    try {
      url = new URL(match[1]).toString();
    } catch {
      continue;
    }
    if (/^https?:\/\//.test(url)) out.push(url);
  }
  return out;
}

async function fetchBraveHtmlRaw(query: string): Promise<string> {
  const proxyUrl = process.env.WEBSEARCH_PROXY;
  if (!proxyUrl) throw new Error("WEBSEARCH_PROXY not set");
  const res = await fetch(`https://search.brave.com/search?q=${encodeURIComponent(query)}`, {
    headers: { "User-Agent": UA, "Accept-Language": "es-ES,es;q=0.9" },
    signal: AbortSignal.timeout(20_000),
    proxy: proxyUrl,
  } as RequestInit & { proxy?: string });
  if (!res.ok) throw new Error(`Brave HTML HTTP ${res.status}`);
  return res.text();
}

export interface WebProfile {
  user: string; // first path segment — the profile user/handle
  url: string;  // full profile URL
}

/**
 * Extract the caption phrase most related to the handle (n-grams of 1-4 words):
 * e.g. caption "…📍Movistar Arena…" + handle "movistararenaes" → "Movistar Arena".
 * Containment scores higher than loose substring matches; ≥2-word phrases
 * preferred; ties broken by shorter length. Shared by X and FB lookups so each
 * mention gets its OWN entity/context (no mixing).
 */
export function entityPhraseForHandle(caption: string, handle: string): string | null {
  const clean = caption
    .replace(/https?:\/\/\S+/g, " ")
    .replace(/#\w+/g, " ")
    .replace(/@\w+/g, " ")
    .replace(/[\u{1F000}-\u{1FAFF}\u{2600}-\u{27BF}\u{2B00}-\u{2BFF}\u{FE0F}\u{200D}]/gu, " ")
    .replace(/[\s•·—–\-]+/g, " ")
    .replace(/\s+/g, " ")
    .trim();
  const norm = (s: string): string => s.toLowerCase().replace(/[^a-z0-9]/g, "");
  const normHandle = norm(handle);
  const stripPunct = (p: string): string => p.replace(/^[¡¿«“'([\]]+|[.,;:!?»”')\]]+$/g, "").trim();
  let best: { phrase: string; quality: number } | null = null;
  for (const sentence of clean.split(/[.\n;!?·•—–\-]+/)) {
    const words = sentence.trim().split(/\s+/).filter(Boolean);
    for (let n = 4; n >= 1; n--) {
      for (let i = 0; i + n <= words.length; i++) {
        const phrase = stripPunct(words.slice(i, i + n).join(" "));
        if (phrase.length < 3) continue;
        const np = norm(phrase);
        if (!np) continue;
        const score = np.includes(normHandle) || normHandle.includes(np) ? 2 : textsRelate(phrase, handle) ? 1 : 0;
        if (!score) continue;
        const quality = score * 100 + (n >= 2 ? 10 : 0) - phrase.length / 1000;
        if (!best || quality > best.quality) best = { phrase, quality };
      }
    }
  }
  return best?.phrase ?? null;
}

/**
 * The caption sentence containing the entity phrase — the minimal PER-MENTION
 * context (avoids mixing other entities of the caption into the lookup).
 */
export function sentenceForEntity(caption: string, phrase: string): string {
  const sentences = caption.split(/(?<=[.!?\n])\s+/);
  const found = sentences.find((s) => s.toLowerCase().includes(phrase.toLowerCase()));
  return (found ?? phrase).trim().slice(0, 300);
}

/**
 * True when two strings share a meaningful chunk (>= 4 chars, case-insensitive,
 * non-alphanumerics stripped) — used to reject lookups that resolved to an
 * unrelated entity (e.g. the artist's page returned for a venue handle).
 * Single shared implementation for the X and Facebook relation guards.
 */
export function textsRelate(a: string, b: string): boolean {
  const normalize = (s: string): string => s.toLowerCase().replace(/[^a-z0-9]/g, "");
  const na = normalize(a);
  const nb = normalize(b);
  if (!na || !nb) return false;
  if (na.includes(nb) || nb.includes(na)) return true;
  const short = na.length <= nb.length ? na : nb;
  const long = short === na ? nb : na;
  for (let len = Math.min(4, short.length); len <= short.length; len++) {
    for (let i = 0; i + len <= short.length; i++) {
      if (long.includes(short.slice(i, i + len))) return true;
    }
  }
  return false;
}

/**
 * Generic profile lookup from search results. Shared by the X and Facebook
 * official-account lookups (single implementation — no duplicated loops).
 */
export async function searchProfile(
  queries: string[],
  hostMatch: (hostname: string) => boolean,
  reserved: Set<string>,
  userRegex: RegExp,
): Promise<WebProfile | null> {
  const candidates = await searchProfileCandidates(queries, hostMatch, reserved, userRegex, 1);
  return candidates[0] ?? null;
}

/** Remove the `locale` query param (and a leftover "?") from search-derived URLs. */
export function stripLocaleParam(url: string): string {
  try {
    const parsed = new URL(url);
    parsed.searchParams.delete("locale");
    parsed.search = parsed.searchParams.toString();
    return parsed.toString();
  } catch {
    return url;
  }
}

/**
 * Like searchProfile but returns up to `max` matching profile candidates
 * (deduplicated by user) so callers can try each with their guards until one
 * passes — robust when engines surface fan accounts before the official one.
 */
export async function searchProfileCandidates(
  queries: string[],
  hostMatch: (hostname: string) => boolean,
  reserved: Set<string>,
  userRegex: RegExp,
  max = 5,
): Promise<WebProfile[]> {
  const found: WebProfile[] = [];
  const seenUsers = new Set<string>();
  for (const query of queries) {
    const urls = await fetchSearchResults(query, 12, hostMatch);
    for (const url of urls) {
      let parsed: URL;
      try {
        parsed = new URL(url);
      } catch {
        continue;
      }
      if (!hostMatch(parsed.hostname.toLowerCase())) continue;
      // Multi-segment paths (/user/posts/...) → the user is the FIRST segment.
      const segments = parsed.pathname.replace(/\/+$/, "").split("/").filter(Boolean);
      if (segments.length === 0) continue;
      const user = decodeURIComponent(segments[0]);
      const key = user.toLowerCase();
      if (!user || seenUsers.has(key) || reserved.has(key) || /^\d+$/.test(user) || !userRegex.test(user)) continue;
      seenUsers.add(key);
      found.push({ user, url: stripLocaleParam(url) });
      if (found.length >= max) return found;
    }
  }
  return found;
}

async function fetchBing(query: string): Promise<string[]> {
  const html = await fetchBingHtml(query);
  const out: string[] = [];
  // Organic results are wrapped in www.bing.com/ck/a?...&u=a1<base64url>
  for (const match of html.matchAll(/u=a1([A-Za-z0-9_-]+)/g)) {
    try {
      const url = Buffer.from(match[1], "base64url").toString("utf8");
      if (/^https?:\/\//.test(url)) out.push(url);
    } catch {
      // skip undecodable
    }
  }
  return out;
}

async function fetchBingHtml(query: string): Promise<string> {
  const res = await fetch(`https://www.bing.com/search?q=${encodeURIComponent(query)}&setlang=es`, {
    headers: { "User-Agent": UA, "Accept-Language": "es-ES,es;q=0.9" },
    signal: AbortSignal.timeout(15_000),
  });
  if (!res.ok) throw new Error(`Bing HTTP ${res.status}`);
  return res.text();
}

async function fetchDuckDuckGo(query: string): Promise<string[]> {
  const html = await fetchDuckDuckGoHtml(query);
  const out: string[] = [];
  for (const match of html.matchAll(/uddg=([^&"]+)/g)) {
    try {
      const url = decodeURIComponent(match[1]);
      if (/^https?:\/\//.test(url)) out.push(url);
    } catch {
      // skip undecodable
    }
  }
  return out;
}

async function fetchDuckDuckGoHtml(query: string): Promise<string> {
  const waitMs = DDG_MIN_INTERVAL_MS - (Date.now() - lastDdgCall);
  if (waitMs > 0) await new Promise((r) => setTimeout(r, waitMs));
  lastDdgCall = Date.now();

  const res = await fetch(`https://html.duckduckgo.com/html/?q=${encodeURIComponent(query)}`, {
    headers: { "User-Agent": UA, "Accept-Language": "es-ES,es;q=0.9" },
    signal: AbortSignal.timeout(8_000),
  });
  if (res.status === 202) throw new Error("DuckDuckGo rate limited (202 anomaly)");
  if (!res.ok) throw new Error(`DuckDuckGo HTTP ${res.status}`);
  return res.text();
}
