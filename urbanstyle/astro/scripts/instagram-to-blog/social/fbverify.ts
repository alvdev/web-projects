import { extractHandles } from "./xverify";
import { groundedCompletion } from "../llm";
import { textsRelate, entityPhraseForHandle, sentenceForEntity, stripLocaleParam } from "./websearch";

export interface FbPageSuggestion {
  handle: string;   // the @token found in the text
  pageName: string; // official FB page name (grounded lookup, Spain rule)
  pageUrl?: string; // official FB page URL when Gemini returns one
  source?: "web" | "gemini"; // which lookup path answered
}

// Verified FB-page cache (in-memory, persisted to state by bot.ts): once a
// page is verified, it never needs the engines/Gemini again.
const knownFbPages = new Map<string, { user: string; url: string }>(); // lowercased handle -> page

export function seedKnownFbPages(record: Record<string, { user: string; url: string }> | undefined): void {
  if (!record) return;
  for (const [handle, page] of Object.entries(record)) knownFbPages.set(handle.toLowerCase(), page);
}
export function dumpKnownFbPages(): Record<string, { user: string; url: string }> {
  return Object.fromEntries(knownFbPages);
}

const FB_MIN_FOLLOWERS = 10_000;

/** Parse a follower/like count string ("8,694,825", "8,7M", "2.1K") → number. */
function parseFollowerNumber(s: string): number | null {
  const t = s.replace(/\s+/g, "");
  const m = t.match(/^([\d.,]+)([KM])?$/i);
  if (!m) return null;
  const suffix = (m[2] ?? "").toUpperCase();
  let num: number;
  if (suffix) num = parseFloat(m[1].replace(/,/g, ".")); // "8,7M" → 8.7
  else num = parseFloat(m[1].replace(/,/g, "")); // "8,694,825" → 8694825
  if (isNaN(num)) return null;
  if (suffix === "K") num *= 1_000;
  if (suffix === "M") num *= 1_000_000;
  return num;
}

/**
 * Resolve the follower/like count of a Facebook page (for the >=10k rule).
 * Source: Gemini grounded — the reliable phrasing is "¿Cuántos likes o
 * seguidores tiene la página de Facebook X? Solo el número." (search snippets
 * proved unreliable: they can surface unrelated small-page counts).
 */
export async function resolveFbPageFollowers(pageName: string): Promise<number | null> {
  const { groundedCompletion } = await import("../llm");
  const prompt = `¿Cuántos likes o seguidores tiene la página de Facebook "${pageName}"? Solo el número.`;
  // Gemini grounding can return empty transiently — retry once.
  for (let attempt = 1; attempt <= 2; attempt++) {
    try {
      const raw = await groundedCompletion(prompt, { temperature: 0.2, maxTokens: 300 });
      const n = parseFollowerNumber(raw);
      if (n !== null) return n;
    } catch (err) {
      console.warn(`[fbverify] followers lookup (Gemini) failed for "${pageName}" (intento ${attempt}):`, (err as Error).message);
    }
  }
  return null;
}

/**
 * The >=10k followers gate. Order:
 * 1) FB page count (Gemini grounded) — known <10k → REJECT.
 * 2) Unknown FB count → cross-check the @handle on X: if the X account has
 *    >=10k followers, accept (the entity is an official big account).
 * 3) Neither → NOT verified (never auto-applies nor enters the caches).
 */
async function verifyFbFollowers(pageName: string, handle: string): Promise<boolean> {
  const count = await resolveFbPageFollowers(pageName);
  if (count !== null) {
    if (count >= FB_MIN_FOLLOWERS) return true;
    console.warn(`[fbverify] "${pageName}": ${count} seguidores (< ${FB_MIN_FOLLOWERS}) — descartada`);
    return false;
  }
  // Unknown FB count → cross-check the handle on X (the text carries X handles).
  const { verifyHandle } = await import("./xverify");
  const info = await verifyHandle(handle);
  if (info.status === "verified" && (info.followers ?? 0) >= FB_MIN_FOLLOWERS) {
    console.warn(`[fbverify] "${pageName}": conteo FB desconocido, aceptada vía X (@${handle}, ${info.followers} seguidores)`);
    return true;
  }
  console.warn(`[fbverify] "${pageName}": seguidores FB desconocidos y X sin 10k (@${handle} ${info.followers ?? "?"}) — no auto-verificada`);
  return false;
}

/**
 * Ask Gemini with Google Search grounding for the OFFICIAL Facebook page of
 * the artist/brand mentioned. This is the SAME prompt system as X's
 * findOfficialHandle (social/xverify.ts) — adapted to Facebook pages.
 * Facebook blocks scraping, so the grounded search result IS the verification.
 */
export async function findOfficialFbPage(
  badHandle: string,
  caption: string,
): Promise<FbPageSuggestion | null> {
  // 1) Web search first (Bing + DuckDuckGo, shared searchProfile): returns the
  // exact page URL — the derived user is correct by construction
  // (e.g. facebook.com/ASanzOficial, facebook.com/ivanferreiro.oficial).
  // Queries: handle-scoped FIRST (a handle is often the page user), then the
  // caption ENTITY PHRASE most related to the handle, asked in natural
  // language ("<entidad> facebook página oficial") — e.g. "Movistar Arena
  // facebook página oficial" for @movistararenaes. The relation guard rejects
  // wrong-page results (e.g. the artist's page returned for a venue handle).
  // 0) Cache first: a previously verified page never needs engines/Gemini.
  const cached = knownFbPages.get(badHandle.toLowerCase());
  if (cached) return { handle: badHandle, pageName: cached.user, pageUrl: cached.url, source: "web" };

  try {
    const { searchProfileCandidates } = await import("./websearch");
    const phrase = entityPhraseForHandle(caption, badHandle);
    const queries = [
      `${badHandle} site:facebook.com`,
      `${badHandle} facebook España`,
      ...(phrase ? [`${phrase} facebook página oficial`, `${phrase} facebook España`] : []),
      `${badHandle} facebook`,
    ];
    const webCandidates = await searchProfileCandidates(
      queries,
      (h) => h === "facebook.com" || h.endsWith(".facebook.com") || h.endsWith(".fb.com"),
      FB_RESERVED_USERS,
      /^[A-Za-z0-9._-]{1,60}$/,
      5,
    );
    // Try each candidate with the relation guard AND the >=10k followers gate.
    for (const web of webCandidates) {
      if (!fbPageRelatesToHandle(web.user, badHandle)) continue;
      if (!(await verifyFbFollowers(web.user, badHandle))) continue;
      knownFbPages.set(badHandle.toLowerCase(), { user: web.user, url: web.url });
      return { handle: badHandle, pageName: web.user, pageUrl: web.url, source: "web" };
    }
    if (webCandidates.length > 0) {
      console.warn(`[fbverify] web candidates rejected for @${badHandle}`);
    }
  } catch (err) {
    console.warn(`[fbverify] web search failed for @${badHandle}:`, (err as Error).message);
  }

  // 2) Gemini with Google Search grounding as the fallback — PER-MENTION
  // scoped: only the entity phrase + its caption sentence, so the model never
  // mixes other entities of the caption (e.g. the artist for a venue handle).
  const phrase = entityPhraseForHandle(caption, badHandle);
  const context = phrase ? sentenceForEntity(caption, phrase) : caption.slice(0, 300);
  const prompt = `El texto menciona a @${badHandle}${phrase ? `, la entidad «${phrase}»` : ""}. Pregunta SOLO por @${badHandle}, ignorando otras entidades del texto (otros artistas/recintos/marcas).

TEXTO (solo la parte relevante):
${context}

¿Cuál es la página OFICIAL en Facebook de @${badHandle}? IMPORTANTE: si tiene varias páginas oficiales por país, elige SIEMPRE la de ESPAÑA. Responde SOLO con el usuario exacto de la página (el que aparece en su URL) y la URL completa, en una línea separados por |. Sin explicación, sin citas ni URLs extra. Si no existe página oficial de Facebook, responde NO.`;

  try {
    const raw = await groundedCompletion(prompt, { temperature: 0.2, maxTokens: 200 });
    const suggestion = parseFbResponse(badHandle, raw);
    if (suggestion && suggestion.pageUrl) {
      if (await verifyFbFollowers(suggestion.pageName, badHandle)) {
        knownFbPages.set(badHandle.toLowerCase(), { user: suggestion.pageName, url: suggestion.pageUrl });
        return { ...suggestion, source: "gemini" as const };
      }
    }
  } catch (err) {
    console.warn(`[fbverify] grounded lookup failed for @${badHandle}:`, (err as Error).message);
  }

  return null;
}

function parseFbResponse(badHandle: string, raw: string): FbPageSuggestion | null {
  // Google grounding injects citation markers like "[citeShakira]" / "[1]".
  // Strip every bracket group, then stray quotes/markdown, mirroring the
  // response discipline X's findOfficialHandle applies via regex extraction.
  const cleaned = raw
    .replace(/\[[^\]]*\]/g, "")
    .replace(/[*"«»“”']/g, "")
    .replace(/\s+/g, " ")
    .trim();
  if (!cleaned) return null;
  // "NO"/"No existe" are negative answers, but a page named e.g. "No Limit
  // Sound" must not be rejected — only treat short negatives or explicit
  // "no existe" as a miss.
  if (/^no\b/i.test(cleaned) && (cleaned.length <= 8 || /no existe/i.test(cleaned))) return null;

  // Optional page URL (facebook.com/...) if Gemini provided one. Extract ALL
  // URLs and prefer the first valid facebook.com one — the model sometimes
  // stutters ("bangtan.official https https://www.facebook.com/…"), leaving
  // orphan protocol tokens that must not leak into the page name.
  const urls = [...cleaned.matchAll(/https?:\/\/[^\s|]+/g)].map((m) => m[0]);
  let pageUrl: string | undefined;
  let name = cleaned;
  const candidate = urls.find((u) => isValidFbPageUrl(u)) ?? urls[0];
  if (candidate) {
    // Remove EVERY url occurrence from the name.
    for (const u of urls) name = name.replace(u, " ");
    name = name.replace(/\|/g, " ").trim();
    if (isValidFbPageUrl(candidate)) {
      pageUrl = stripLocaleParam(candidate);
      // The exact user/mention is the path segment of the URL (the model's
      // pretty name is unreliable) — e.g. facebook.com/alejandrosanz → "alejandrosanz".
      const user = decodeURIComponent(candidate.replace(/\/+$/, "").split("/").pop() ?? "");
      if (user && /^[A-Za-z0-9._-]{1,60}$/.test(user)) {
        name = user;
      }
    }
  } else {
    name = cleaned.replace(/\|/g, " ").trim();
  }
  name = name
    .replace(/^["']|["']$/g, "")
    // Strip orphan protocol tokens the model may leave behind ("http", "https").
    .replace(/\bhttps?:?\/?\/?/gi, " ")
    .replace(/\s+/g, " ")
    .trim();
  if (!name) return null;
  // Malformed leftover protocol text → discard (never render a corrupted name).
  if (/\bhttps?\b/i.test(name)) {
    console.warn(`[fbverify] grounded result "${name}" still contains protocol junk — discarded`);
    return null;
  }
  if (name.length > 80) return null;
  // Same guard as the web path: a suggestion unrelated to the queried handle
  // (e.g. Gemini returning the artist's page for a venue handle) is discarded.
  if (!fbPageRelatesToHandle(name, badHandle)) {
    console.warn(`[fbverify] grounded result "${name}" unrelated to @${badHandle} — discarded`);
    return null;
  }
  // ALWAYS carry a direct official page URL: if Gemini gave none and the name
  // is a valid single-token user, construct it; otherwise the suggestion is
  // unusable (a multi-word name cannot be a page URL) → discard. Never falls
  // back to a Facebook search link.
  if (!pageUrl) {
    if (/^[A-Za-z0-9._-]{1,60}$/.test(name)) {
      pageUrl = `https://www.facebook.com/${encodeURIComponent(name)}`;
    } else {
      console.warn(`[fbverify] grounded result "${name}" has no usable URL — discarded`);
      return null;
    }
  }
  return { handle: badHandle, pageName: name, pageUrl };
}

/** Reserved path segments that are never a page username. */
const FB_RESERVED_USERS = new Set([
  "pages", "search", "events", "groups", "stories", "share", "login", "policy",
  "help", "marketplace", "me", "settings", "watch", "reel", "shorts", "saved",
  "friends", "photos", "profile", "profile.php", "messages", "notifications", "facebook", "www", "m", "l",
]);

/** True only for well-formed http(s) URLs on the facebook.com domain. */
export function isValidFbPageUrl(url: string): boolean {
  try {
    const parsed = new URL(url);
    return (
      (parsed.protocol === "https:" || parsed.protocol === "http:") &&
      (parsed.hostname === "facebook.com" || parsed.hostname.endsWith(".facebook.com") || parsed.hostname.endsWith(".fb.com"))
    );
  } catch {
    return false;
  }
}

/**
 * True when the page user shares a meaningful substring (>= 4 chars) with the
 * queried handle — guards against engines returning the artist's page for a
 * venue/brand handle. Thin wrapper over the shared textsRelate helper.
 */
export function fbPageRelatesToHandle(pageUser: string, handle: string): boolean {
  return textsRelate(pageUser, handle);
}

/**
 * For each @mention in the text, find the official Facebook page via grounded
 * Gemini (findOfficialFbPage). Mirrors the X flow: every mention is treated as
 * unverified (FB blocks scraping), so all of them get the grounded lookup.
 */
export async function suggestFbPages(text: string, caption: string): Promise<FbPageSuggestion[]> {
  const handles = extractHandles(text);
  if (handles.length === 0) return [];

  const suggestions: FbPageSuggestion[] = [];
  for (const handle of handles) {
    const suggestion = await findOfficialFbPage(handle, caption);
    if (!suggestion) continue;
    if (suggestions.some((s) => s.handle.toLowerCase() === handle.toLowerCase())) continue;
    // Skip if the same page (same URL, or same derived user when no URL) was
    // already suggested for another handle — the lookup likely failed and
    // returned the wrong entity.
    if (suggestions.some((s) => (s.pageUrl ?? s.pageName).toLowerCase() === (suggestion.pageUrl ?? suggestion.pageName).toLowerCase())) continue;
    suggestions.push(suggestion);
  }
  return suggestions;
}
