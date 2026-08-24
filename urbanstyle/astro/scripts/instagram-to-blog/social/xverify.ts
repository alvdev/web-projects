/**
 * X @handle verification via profile page scraping (no API needed).
 * Fetches https://x.com/<handle> and parses the embedded state for
 * followers + verified flag. 403/429 (anti-bot) → retries with backoff,
 * then reports "unverified".
 */

export interface HandleInfo {
  handle: string;
  status: "verified" | "invalid" | "unverified";
  exists?: boolean;
  followers?: number;
  isVerified?: boolean;
  displayName?: string;
  error?: string;
}

const UA =
  "Mozilla/5.0 (X11; Linux x86_64; rv:150.0) Gecko/20100101 Firefox/150.0";

function parseProfile(html: string, handle: string): HandleInfo {
  const base: HandleInfo = {
    handle,
    status: "verified",
    exists: true,
  };

  const nameMatch = html.match(/<meta property="og:title" content="([^"]*\(@[^)]+\) on X)"/);
  if (nameMatch?.[1]) base.displayName = nameMatch[1];

  const followers = html.match(/followers:(\d+)/);
  if (followers?.[1]) base.followers = Number(followers[1]);

  // X embeds the blue checkmark in the UserVerification state:
  // verified:!1,is_blue_verified:!0  → blue-verified (true)
  // verified:!0,is_blue_verified:!1  → not blue-verified (false)
  const blueVerified = html.match(/is_blue_verified:!([01])/);
  if (blueVerified?.[1]) {
    base.isVerified = blueVerified[1] === "0";
  }

  // If we found no display name and no followers, this is likely a
  // "no such user" page that X serves with HTTP 200 — treat as invalid.
  if (!base.displayName && !base.followers) {
    return { handle, status: "invalid", exists: false };
  }

  return base;
}

export async function verifyHandle(handle: string, maxRetries = 3): Promise<HandleInfo> {
  const clean = handle.replace(/^@/, "");
  let delay = 30_000;

  for (let attempt = 1; attempt <= maxRetries; attempt++) {
    try {
      const res = await fetch(`https://x.com/${clean}`, {
        headers: {
          "User-Agent": UA,
          "Accept-Language": "en-US,en;q=0.9",
        },
        redirect: "follow",
        signal: AbortSignal.timeout(30_000),
      });

      if (res.status === 200) {
        const html = await res.text();
        return parseProfile(html, clean);
      }
      if (res.status === 404) {
        return { handle: clean, status: "invalid", exists: false };
      }
      // 403/429/5xx → retry with backoff
      console.warn(`[xverify] ${clean}: HTTP ${res.status}, retry ${attempt}/${maxRetries}`);
    } catch (err) {
      console.warn(`[xverify] ${clean}: fetch error (${(err as Error).message}), retry ${attempt}/${maxRetries}`);
    }

    if (attempt < maxRetries) {
      await new Promise((r) => setTimeout(r, delay));
      delay *= 2;
    }
  }

  return { handle: clean, status: "unverified", error: "X blocked verification (403/429)" };
}

export function extractHandles(text: string): string[] {
  // Word-boundary guards so email local parts (hola@urbanstyle.com) are not
  // mistaken for handles. Deduplicated CASE-INSENSITIVELY (x.com handles are
  // case-insensitive): "Deivpr" and "deivpr" are the same account.
  const matches = text.match(/(?<![A-Za-z0-9_])@([A-Za-z0-9_]{1,15})(?![A-Za-z0-9_])/g) ?? [];
  return [...new Map(matches.map((m) => [m.slice(1).toLowerCase(), m.slice(1)])).values()];
}

export function formatHandleStatus(info: HandleInfo): string {
  const followers = info.followers
    ? `${info.followers >= 1_000_000 ? (info.followers / 1_000_000).toFixed(1) + "M" : info.followers >= 1000 ? (info.followers / 1000).toFixed(0) + "K" : String(info.followers)} seguidores`
    : "n/a";
  switch (info.status) {
    case "verified":
      return `✅ @${info.handle} — ${info.isVerified ? "verificado" : "no verificado"} · ${followers}`;
    case "invalid":
      return `❌ @${info.handle} — no existe en X`;
    default:
      return `⚠️ @${info.handle} — no se pudo verificar (${info.error ?? "bloqueado por X"})`;
  }
}

export async function verifyTweetHandles(text: string): Promise<HandleInfo[]> {
  const handles = extractHandles(text);
  const results: HandleInfo[] = [];
  for (const handle of handles) {
    results.push(await verifyHandle(handle));
  }
  return results;
}

/**
 * Ask Gemini with Google Search grounding for the OFFICIAL X handle of the
 * artist/brand mentioned in the caption (when a tweet handle is invalid or a
 * fan account). Returns the grounded official handle, verified.
 *
 * IMPORTANT: if the artist/brand has country-specific accounts, the SPAIN
 * account is always the one to use (the campaign targets Spain).
 */
export async function findOfficialHandle(
  badHandle: string,
  caption: string,
  verify: (h: string) => Promise<HandleInfo> = verifyHandle,
): Promise<HandleInfo | null> {
  // 1) Web search first (Bing + DuckDuckGo, shared searchProfile): the same
  // kind of search that returns the correct official account (e.g. Google
  // says @Anuel_2bleA); the candidate is then verified by scraping x.com.
  try {
    const { searchProfile, buildSearchQueries, textsRelate } = await import("./websearch");
    // The handle itself first (Spain-qualified before the generic), then
    // caption fragments (find the official when the mention does not exist).
    const web = await searchProfile(
      [
        `${badHandle} twitter cuenta oficial`,
        `${badHandle} twitter España`,
        ...buildSearchQueries(caption).map((f) => `${f} twitter cuenta oficial`),
      ],
      (h) => h === "x.com" || h.endsWith(".x.com") || h === "twitter.com" || h.endsWith(".twitter.com"),
      X_RESERVED_HANDLES,
      /^[A-Za-z0-9_]{1,15}$/,
    );
    // Never accept a candidate unrelated to the queried handle (e.g. the
    // artist's account returned for a venue handle).
    if (web && textsRelate(web.user, badHandle)) return await verify(web.user);
    if (web) {
      console.warn(`[xverify] web result "@${web.user}" unrelated to @${badHandle} — discarded`);
    }
  } catch (err) {
    console.warn(`[xverify] web search failed for @${badHandle}:`, (err as Error).message);
  }

  // 2) Gemini with Google Search grounding as the fallback — PER-MENTION
  // scoped (only the entity phrase + its caption sentence), so the model
  // never mixes other entities of the caption (e.g. the artist for a venue).
  const { entityPhraseForHandle, sentenceForEntity } = await import("./websearch");
  const phrase = entityPhraseForHandle(caption, badHandle);
  const context = phrase ? sentenceForEntity(caption, phrase) : caption.slice(0, 300);
  const { groundedCompletion } = await import("../llm");
  const prompt = `El texto menciona a @${badHandle}${phrase ? `, la entidad «${phrase}»` : ""}. Pregunta SOLO por @${badHandle}, ignorando otras entidades del texto (otros artistas/recintos/marcas).

TEXTO (solo la parte relevante):
${context}

¿Cuál es la cuenta OFICIAL en X (Twitter) de @${badHandle}? IMPORTANTE: si tiene varias cuentas oficiales por país, elige SIEMPRE la cuenta de ESPAÑA. Responde SOLO con el @handle exacto, sin explicación. Si no existe cuenta oficial, responde NO.`;

  try {
    const raw = await groundedCompletion(prompt, { temperature: 0.2, maxTokens: 200 });
    // Robust parser: natural-language answers ("El handle oficial es @X")
    // must not fool a first-token regex — try every @handle in the response.
    const { textsRelate } = await import("./websearch");
    const handles = [...new Set([...raw.matchAll(/@([A-Za-z0-9_]{1,15})/g)].map((m) => m[1]))];
    for (const h of handles) {
      if (!textsRelate(h, badHandle)) {
        console.warn(`[xverify] grounded result "@${h}" unrelated to @${badHandle} — discarded`);
        continue;
      }
      return await verify(h);
    }
    return null;
  } catch (err) {
    console.warn(`[xverify] official-handle grounded lookup failed for @${badHandle}:`, (err as Error).message);
    return null;
  }
}

/** Reserved path segments that are never a profile handle. */
const X_RESERVED_HANDLES = new Set([
  "explore", "home", "search", "intent", "share", "i", "events", "hashtag",
  "settings", "login", "signup", "notifications", "messages", "compose",
  "tos", "privacy", "about", "account", "download", "help", "x", "twitter", "m",
]);

// Verified artist-handle cache (in-memory, persisted to state by bot.ts):
// once a lookup verifies an official handle, it never needs the engines again.
const knownArtistHandles = new Map<string, string>(); // normalized artist name -> handle

export function seedKnownArtistHandles(record: Record<string, string> | undefined): void {
  if (!record) return;
  for (const [name, handle] of Object.entries(record)) knownArtistHandles.set(name.toLowerCase(), handle);
}
export function dumpKnownArtistHandles(): Record<string, string> {
  return Object.fromEntries(knownArtistHandles);
}
function normalizeArtist(name: string): string {
  return name.toLowerCase().replace(/[^a-z0-9]/g, "");
}

/**
 * Try each @handle found in a Gemini raw response (natural-language answers
 * like "El handle oficial es @deivpr" must not fool the parser): relation
 * guard + verify + official-look criterion.
 */
async function pickOfficialFromRaw(
  raw: string,
  artistName: string,
  verify: (h: string) => Promise<HandleInfo>,
  accept: (info: HandleInfo) => HandleInfo | null,
): Promise<HandleInfo | null> {
  const { textsRelate } = await import("./websearch");
  const handles = [...new Set([...raw.matchAll(/@([A-Za-z0-9_]{1,15})/g)].map((m) => m[1]))];
  for (const h of handles) {
    if (!textsRelate(h, artistName)) {
      console.warn(`[xverify] grounded result "@${h}" unrelated to "${artistName}" — discarded`);
      continue;
    }
    const official = accept(await verify(h));
    if (official) return official;
  }
  return null;
}

/**
 * Find the official X handle of an artist/brand NAME (plain text, not a
 * mention) via web search + x.com scraping verification. Used to inject the
 * artist's mention when the LLM wrote the plain name instead of the @handle.
 */
export async function findArtistHandle(
  artistName: string,
  caption: string,
  verify: (h: string) => Promise<HandleInfo> = verifyHandle,
): Promise<HandleInfo | null> {
  // Only accept accounts with a real following: >=10k followers (the blue check
// is NOT a bypass — small accounts can buy it; e.g. @pieldeasfalto 321
// followers was blue and had to be rejected).
  const acceptOfficial = (info: HandleInfo): HandleInfo | null => {
    if (info.status !== "verified") return null;
    if ((info.followers ?? 0) >= 10_000) return info;
    console.warn(`[xverify] artist-handle "@${info.handle}" too small (${info.followers ?? 0} followers) — not injected`);
    return null;
  };

  // 0) Cache first: a previously verified official handle never needs engines.
  const cached = knownArtistHandles.get(normalizeArtist(artistName));
  if (cached) {
    const official = acceptOfficial(await verify(cached));
    if (official) return official;
  }

  try {
    const { searchProfileCandidates, buildSearchQueries, textsRelate } = await import("./websearch");
    const nameCompact = artistName.replace(/\s+/g, "");
    const webCandidates = await searchProfileCandidates(
      [
        `${artistName} twitter cuenta oficial`,
        `${artistName} twitter`,
        `${nameCompact} twitter`,
        `${artistName} twitter España`,
        ...buildSearchQueries(caption).map((f) => `${f} twitter cuenta oficial`),
      ],
      (h) => h === "x.com" || h.endsWith(".x.com") || h === "twitter.com" || h.endsWith(".twitter.com"),
      X_RESERVED_HANDLES,
      /^[A-Za-z0-9_]{1,15}$/,
      5,
    );
    for (const web of webCandidates) {
      if (!textsRelate(web.user, artistName)) continue;
      const official = acceptOfficial(await verify(web.user));
      if (official) {
        knownArtistHandles.set(normalizeArtist(artistName), official.handle);
        return official;
      }
    }
    if (webCandidates.length > 0) {
      console.warn(`[xverify] artist-handle web candidates unrelated/unofficial for "${artistName}" — discarded`);
    }
  } catch (err) {
    console.warn(`[xverify] artist-handle search failed for "${artistName}":`, (err as Error).message);
  }

  // Gemini grounded as fallback (same system as the other lookups).
  const { groundedCompletion } = await import("../llm");
  const prompt = `¿Cuál es el handle oficial en X (Twitter) del artista/músico "${artistName}"?

CONTEXTO (caption de Instagram):
${caption.slice(0, 400)}

IMPORTANTE: si tiene varias cuentas oficiales por país, elige SIEMPRE la de ESPAÑA. Responde SOLO con el @handle exacto, sin explicación. Si no existe cuenta oficial, responde NO.`;

  try {
    const raw = await groundedCompletion(prompt, { temperature: 0.2, maxTokens: 200 });
    const official = await pickOfficialFromRaw(raw, artistName, verify, acceptOfficial);
    if (official) knownArtistHandles.set(normalizeArtist(artistName), official.handle);
    return official;
  } catch (err) {
    console.warn(`[xverify] artist-handle grounded lookup failed for "${artistName}":`, (err as Error).message);
    return null;
  }
}

/**
 * Ask the LLM for up to 3 candidate X handles for a given artist/brand name,
 * then verify each and return the ranked results.
 */
export async function suggestCandidates(
  name: string,
  verify: (h: string) => Promise<HandleInfo> = verifyHandle,
): Promise<HandleInfo[]> {
  const { generateTextCompletion } = await import("../llm");
  const prompt = `List the 3 most likely Twitter/X usernames (without the @ symbol, separated by commas) for this famous artist/brand: "${name}". Only respond with the usernames, no explanation. If unsure, give the most plausible ones.`;

  try {
    const raw = await generateTextCompletion(prompt, "gemini", { temperature: 0.3, maxTokens: 200 });
    const candidates: string[] = raw
      .split(/[,\n]/)
      .map((c: string) => c.trim().replace(/^@/, "").replace(/\s+/g, ""))
      .filter((c: string) => /^[A-Za-z0-9_]{1,15}$/.test(c))
      .slice(0, 3);

    const results: HandleInfo[] = [];
    for (const candidate of candidates) {
      if (results.some((r) => r.handle.toLowerCase() === candidate.toLowerCase())) continue;
      results.push(await verify(candidate));
    }
    return results;
  } catch (err) {
    console.warn(`[xverify] candidate suggestion failed for "${name}":`, (err as Error).message);
    return [];
  }
}
