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
  return Array.from(new Set(text.match(/@([A-Za-z0-9_]{1,15})/g) ?? [])).map((h) => h.replace(/^@/, ""));
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
  const { groundedCompletion } = await import("../llm");
  const prompt = `El siguiente texto menciona a @${badHandle}, que NO es la cuenta oficial en X (Twitter) del artista/marca mencionado (no existe o es una cuenta fan).

TEXTO:
${caption.slice(0, 600)}

¿Cuál es la cuenta OFICIAL en X (Twitter) del artista/marca mencionado? IMPORTANTE: si el artista/marca tiene varias cuentas oficiales por país, elige SIEMPRE la cuenta de ESPAÑA. Responde SOLO con el @handle exacto, sin explicación.`;

  try {
    const raw = await groundedCompletion(prompt, { temperature: 0.2, maxTokens: 200 });
    const match = raw.match(/@?([A-Za-z0-9_]{1,15})/);
    if (!match?.[1]) return null;
    return await verify(match[1]);
  } catch (err) {
    console.warn(`[xverify] official-handle lookup failed for @${badHandle}:`, (err as Error).message);
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
