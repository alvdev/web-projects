import { generateTextCompletion } from "./llm";
import type { PreparedPost } from "./types";

const SPANISH_MONTHS: Record<string, number> = {
  enero: 0, febrero: 1, marzo: 2, abril: 3, mayo: 4, junio: 5,
  julio: 6, agosto: 7, septiembre: 8, octubre: 9, noviembre: 10, diciembre: 11,
};

/**
 * Determine whether the tweet should be written in PAST tense:
 * - the IG post is more than 1 week old, or
 * - a date found in the caption (e.g. "10 de abril", "10/04", "10.04") is before today.
 */
export function shouldUsePastTense(caption: string, postTimestamp?: string): boolean {
  // Rule 1: post older than 1 week
  if (postTimestamp) {
    const postDate = new Date(postTimestamp).getTime();
    if (!Number.isNaN(postDate)) {
      const oneWeekAgo = Date.now() - 7 * 24 * 60 * 60 * 1000;
      if (postDate < oneWeekAgo) return true;
    }
  }

  // Rule 2: an event date in the caption is in the past
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate()).getTime();

  const datePatterns = [
    /(\d{1,2})\s+de\s+(enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)/gi,
    /(\d{1,2})[\/.](\d{1,2})/g,
  ];

  for (const pattern of datePatterns) {
    let match: RegExpExecArray | null;
    while ((match = pattern.exec(caption)) !== null) {
      let month: number;
      let day: number;
      if (match[2] && SPANISH_MONTHS[match[2].toLowerCase()] !== undefined) {
        day = Number(match[1]);
        month = SPANISH_MONTHS[match[2].toLowerCase()];
      } else if (match[2] && !isNaN(Number(match[2]))) {
        // dd/mm or dd.mm
        day = Number(match[1]);
        month = Number(match[2]) - 1;
      } else {
        continue;
      }
      if (month < 0 || month > 11 || day < 1 || day > 31) continue;
      const eventDate = new Date(now.getFullYear(), month, day).getTime();
      if (eventDate < today) return true;
    }
  }

  return false;
}

/**
 * Build the tweet prompt from the published blog post AND the original
 * Instagram caption. The tweet must be based on the IG caption (artists,
 * dates, cities, song names, @handles), in the account's established style,
 * with the blog URL at the end. Total must fit X's 280-char limit — the URL
 * counts as 23 chars (t.co), so the text body targets ~250 chars.
 */
export function buildTweetPrompt(post: PreparedPost, caption: string, postTimestamp?: string): string {
  const pastTense = shouldUsePastTense(caption, postTimestamp);
  const tenseRule = pastTense
    ? `- IMPORTANT: the event/campaign described has ALREADY happened. Write the tweet in PAST tense: "Así quedaron los carteles que pegamos…" (not "Así han quedado los carteles que hemos pegado…"), "El fenómeno argentino llegó con sus hits…" (not "llega…"), "los carteles que pegamos" (not "hemos pegado").`
    : `- Write the tweet in PRESENT tense (the campaign is current).`;

  return `Write a Spanish tweet for the Twitter/X account @pegadacarteles, a street marketing (wild posting / pegada de carteles) company, promoting this new blog article.

BLOG POST TITLE: ${post.title}
BLOG POST DESCRIPTION: ${post.description}
BLOG POST URL: https://urbanstylepublicity.com/blog/${post.slug}

ORIGINAL INSTAGRAM CAPTION (the tweet MUST be based on this text — use its real details: artists, bands, dates, cities, venues, song names, @handles, campaign details):
${caption || "(no caption available)"}

EXAMPLES of the account's established tweet style (follow this tone and level of specificity):
- "Aquí están los carteles de @TruenoOficial y @itsFeid. Se juntan para romperla con su nueva canción llamada \"Cruz\". Una vibra unida, un flow inigualable y una energía que no podrás sacarte de la cabeza."
- "¿Viste el cartel de despedida de @_AndyyLucas_? Después de tantos años de música y emociones, llega el momento de decir adiós con su gira \"Nuestros últimos acordes\" en una despedida irrepetible con artistas invitados"
- "Ya se puede ver pegado el cartel de @pablolopezmusic anunciando sus conciertos en Madrid y Barcelona el 21 y 26 de junio, respectivamente."
- "El cartel que hemos pegado para que artistas como Olivia Rodrigo, Kings of Leon, Thirty Seconds to Mars, Residente y muchos más actúen este verano en @madcoolfestival."

RULES:
- The TEXT BODY (everything before the URL) must be at most 250 characters. The URL will be appended separately, so do NOT include the URL in your response — just the tweet body text.
- Base the tweet ONLY on the real details present in the Instagram caption and the blog title/description. Do not invent dates, cities, artists or song names that are not there. Use the exact names, dates and cities from the caption.
- Natural, colloquial Spanish, like a real person — specific and concrete, not generic. Mention the actual artists/events/dates/cities from the caption.
- MENTIONS: use the CORRECT Twitter/X handle of the artist/brand based on your knowledge — never blindly copy the Instagram handle from the caption (IG and X handles often differ). If the artist/brand has multiple official accounts by country, ALWAYS use the SPAIN account. If you do not know the artist's real X handle with confidence, do NOT write an @mention — just use the plain name.
- RAE: any work title (album, tour, song, event) must be in double quotes, e.g. "Cruz" or "Nuestros últimos acordes".
- ${tenseRule}
- Do NOT mention that there is a blog article, do not say "blog" or "artículo".
- Respond with ONLY the tweet body text, no surrounding quotes, no URL, no explanation.`;
}

const BLOG_URL_BASE = "https://urbanstylepublicity.com/blog/";

/**
 * Generate the tweet with BOTH providers (Gemini + DeepSeek), each
 * independently. The URL is appended in code after generation (the LLM only
 * writes the body), and the result is enforced to fit X's 280-char limit.
 */
export async function generateTweets(
  post: PreparedPost,
  caption = "",
  postTimestamp?: string,
): Promise<{ gemini?: string; deepseek?: string }> {
  const prompt = buildTweetPrompt(post, caption, postTimestamp);
  const url = `${BLOG_URL_BASE}${post.slug}`;
  const MAX_BODY = 280 - 23; // URL counts as 23 chars on X (t.co)

  const finalize = (raw: string): string => {
    let body = raw.trim().replace(/^["']|["']$/g, "");
    // Strip any URL the model may have included despite the instruction
    body = body.replace(/https?:\/\/\S+/g, "").replace(/\s+/g, " ").trim();
    if (body.length > MAX_BODY) {
      // Hard cap at the last word boundary to fit the limit
      body = body.slice(0, MAX_BODY);
      const lastSpace = body.lastIndexOf(" ");
      if (lastSpace > 60) body = body.slice(0, lastSpace);
    }
    return `${body} ${url}`.trim();
  };

  const [geminiResult, deepseekResult] = await Promise.allSettled([
    generateTextCompletion(prompt, "gemini", { temperature: 0.8, maxTokens: 4000 }),
    generateTextCompletion(prompt, "deepseek", { temperature: 0.8, maxTokens: 4000 }),
  ]);

  const tweets: { gemini?: string; deepseek?: string } = {};
  if (geminiResult.status === "fulfilled") {
    tweets.gemini = finalize(geminiResult.value);
  } else {
    console.warn("[tweet] Gemini failed:", (geminiResult.reason as Error).message);
  }
  if (deepseekResult.status === "fulfilled") {
    tweets.deepseek = finalize(deepseekResult.value);
  } else {
    console.warn("[tweet] DeepSeek failed:", (deepseekResult.reason as Error).message);
  }

  if (!tweets.gemini && !tweets.deepseek) {
    throw new Error("Both LLMs failed to generate a tweet");
  }
  return tweets;
}

export function tweetUrlFor(post: PreparedPost): string {
  return `https://urbanstylepublicity.com/blog/${post.slug}`;
}

export function formatTweetPreview(text: string): string {
  return `🐦 *Tweet (${text.length} caracteres):*\n\n${text}`;
}
