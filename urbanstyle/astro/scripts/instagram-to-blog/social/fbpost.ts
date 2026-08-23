import { generateTextCompletion } from "../llm";
import type { PreparedPost } from "../types";
import { shouldUsePastTense } from "../tweet";

const BLOG_URL_BASE = "https://urbanstylepublicity.com/blog/";

/**
 * Facebook post prompt: same style/rules as the X tweet (based on the IG
 * caption, RAE quotes, Spain account rule, past-tense detection) but with no
 * 280-char limit — 2-3 sentences, concise.
 */
export function buildFbPrompt(post: PreparedPost, caption: string, postTimestamp?: string): string {
  const pastTense = shouldUsePastTense(caption, postTimestamp);
  const tenseRule = pastTense
    ? `- IMPORTANT: the event/campaign described has ALREADY happened. Write the post in PAST tense: "Así quedaron los carteles que pegamos…" (not "Así han quedado los carteles que hemos pegado…"), "El fenómeno argentino llegó con sus hits…" (not "llega…"), "los carteles que pegamos" (not "hemos pegado").`
    : `- Write the post in PRESENT tense (the campaign is current).`;

  return `Write a short Spanish Facebook post for the page "Urban Style Publicity", a street marketing (wild posting / pegada de carteles) company, promoting this new blog article.

BLOG POST TITLE: ${post.title}
BLOG POST DESCRIPTION: ${post.description}
BLOG POST URL: https://urbanstylepublicity.com/blog/${post.slug}

ORIGINAL INSTAGRAM CAPTION (the post MUST be based on this text — use its real details: artists, bands, dates, cities, venues, song names, campaign details):
${caption || "(no caption available)"}

EXAMPLES of the account's established style (follow this tone and level of specificity):
- "Aquí están los carteles de @TruenoOficial y @itsFeid. Se juntan para romperla con su nueva canción llamada \"Cruz\". Una vibra unida, un flow inigualable y una energía que no podrás sacarte de la cabeza."
- "¿Viste el cartel de despedida de @_AndyyLucas_? Después de tantos años de música y emociones, llega el momento de decir adiós con su gira \"Nuestros últimos acordes\" en una despedida irrepetible con artistas invitados"
- "Ya se puede ver pegado el cartel de @pablolopezmusic anunciando sus conciertos en Madrid y Barcelona el 21 y 26 de junio, respectivamente."
- "El cartel que hemos pegado para que artistas como Olivia Rodrigo, Kings of Leon, Thirty Seconds to Mars, Residente y muchos más actúen este verano en @madcoolfestival."

RULES:
- 2 to 3 sentences, natural, colloquial Spanish, like a real person — specific and concrete, not generic. Mention the actual artists/events/dates/cities from the caption.
- Base the post ONLY on the real details present in the Instagram caption and the blog title/description. Do not invent dates, cities, artists or song names that are not there.
- MENTIONS: use the CORRECT Facebook page user of the artists AND venues/stadiums/brands mentioned in the caption (e.g. "Movistar Arena" → the page user in its URL, like facebook.com/movistararenaes) based on your knowledge — never blindly copy an Instagram or X handle (IG/X handles usually differ from the Facebook page). If the artist/brand has multiple official accounts by country, ALWAYS use the SPAIN account. If you do not know the real Facebook page user with confidence, do NOT write an @mention — just use the plain name.
- RAE: any work title (album, tour, song, event) must be in double quotes, e.g. "Cruz" or "Nuestros últimos acordes".
- ${tenseRule}
- Do NOT mention that there is a blog article, do not say "blog" or "artículo".
- Do NOT include any URL in your response — the link is appended automatically.
- Respond with ONLY the post text, no surrounding quotes, no URL, no explanation.`;
}

const finalize = (raw: string): string => {
  let body = raw.trim().replace(/^["']|["']$/g, "");
  body = body.replace(/https?:\/\/\S+/g, "").replace(/\s+/g, " ").trim();
  return body;
};

/**
 * Generate the FB post text with BOTH providers (Gemini + DeepSeek). The URL
 * is appended in code after generation (the LLM only writes the body).
 */
export async function generateFbTexts(
  post: PreparedPost,
  caption = "",
  postTimestamp?: string,
): Promise<{ gemini?: string; deepseek?: string }> {
  const prompt = buildFbPrompt(post, caption, postTimestamp);
  const url = `${BLOG_URL_BASE}${post.slug}`;

  const [geminiResult, deepseekResult] = await Promise.allSettled([
    generateTextCompletion(prompt, "gemini", { temperature: 0.8, maxTokens: 8000 }),
    generateTextCompletion(prompt, "deepseek", { temperature: 0.8, maxTokens: 8000 }),
  ]);

  const texts: { gemini?: string; deepseek?: string } = {};
  if (geminiResult.status === "fulfilled") {
    texts.gemini = `${finalize(geminiResult.value)} ${url}`.trim();
  } else {
    console.warn("[fbpost] Gemini failed:", (geminiResult.reason as Error).message);
  }
  if (deepseekResult.status === "fulfilled") {
    texts.deepseek = `${finalize(deepseekResult.value)} ${url}`.trim();
  } else {
    console.warn("[fbpost] DeepSeek failed:", (deepseekResult.reason as Error).message);
  }

  if (!texts.gemini && !texts.deepseek) {
    throw new Error("Both LLMs failed to generate a Facebook post");
  }
  return texts;
}
