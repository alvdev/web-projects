import OpenAI from "openai";
import { GoogleGenerativeAI, type GenerateContentResult } from "@google/generative-ai";
import { LlmArticleSchema, type LlmArticle, type LlmProvider, type NewPost } from "./types";
import { loadInstructions, formatInstructions, markInstructionsApplied } from "./instructions";

const MAX_RETRIES = 3;
const BASE_DELAY_MS = 2000;

// Availability retry: on 503/429, wait 30s -> 60s -> 120s -> 120s... until the provider
// is back. 30s is far below any rate limit; 503 means the server itself asks to
// retry later, so escalating slowly is safe and effective. Keeps trying forever.
const AVAILABILITY_INTERVALS_MS = [30_000, 60_000, 120_000, 120_000];

const deepseekClient = new OpenAI({
  apiKey: process.env.OPENCODE_API_KEY ?? "",
  baseURL: process.env.OPENCODE_BASE_URL ?? "https://opencode.ai/zen/go/v1",
  timeout: 180_000,
  maxRetries: 0,
});

const DEEPSEEK_MODEL = process.env.OPENCODE_MODEL ?? "deepseek-v4-flash";

const gemini = new GoogleGenerativeAI(process.env.GEMINI_API_KEY ?? "");

// Gemini model chain (priority order). On 429 (quota/rate limit) the next model
// is tried immediately; on 503 (high demand) the same model is retried with
// escalation. Falls back to GEMINI_MODEL if the list is not set.
function geminiModelChain(): string[] {
  const legacy = process.env.GEMINI_MODEL ?? "gemini-3.6-flash";
  const list = (process.env.GEMINI_MODELS ?? legacy)
    .split(",")
    .map((m) => m.trim())
    .filter(Boolean);
  return list.length > 0 ? list : [legacy];
}

const imageDescriptionCache = new Map<string, string>();

/** 503 = high demand — retry the SAME model with escalation (switching models does not help). */
function is503(err: unknown): boolean {
  return err instanceof Error && /\b503\b/i.test(err.message);
}

/** 429 = quota/rate limit — advancing to the next model helps; waiting for the same one usually does not. */
function is429(err: unknown): boolean {
  return err instanceof Error && /\b429\b/i.test(err.message);
}

async function delay(ms: number): Promise<void> {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Run fn; if it fails with 503 (high demand), keep retrying with escalating waits
 * (30s -> 60s -> 120s -> 120s...) until the provider is available again.
 * 429 is NOT retried here — it bubbles up so the model chain can advance.
 */
async function withAvailability503<T>(label: string, fn: () => Promise<T>): Promise<T> {
  let intervalIndex = 0;
  for (;;) {
    try {
      return await fn();
    } catch (err) {
      if (!is503(err)) throw err;
      const waitMs =
        intervalIndex < AVAILABILITY_INTERVALS_MS.length
          ? AVAILABILITY_INTERVALS_MS[intervalIndex]
          : AVAILABILITY_INTERVALS_MS[AVAILABILITY_INTERVALS_MS.length - 1];
      if (intervalIndex < AVAILABILITY_INTERVALS_MS.length - 1) intervalIndex++;
      console.warn(`[llm] ${label}: 503 unavailable (high demand). Waiting ${waitMs / 1000}s before retrying...`);
      await delay(waitMs);
    }
  }
}

/**
 * Run fn; if it fails with 503 OR 429, keep retrying with escalating waits.
 * Used at the top level: both mean "unavailable right now", and retrying the
 * whole chain (which starts from the primary model again) is safe.
 */
async function withAvailability<T>(label: string, fn: () => Promise<T>): Promise<T> {
  let intervalIndex = 0;
  for (;;) {
    try {
      return await fn();
    } catch (err) {
      if (!is503(err) && !is429(err)) throw err;
      const waitMs =
        intervalIndex < AVAILABILITY_INTERVALS_MS.length
          ? AVAILABILITY_INTERVALS_MS[intervalIndex]
          : AVAILABILITY_INTERVALS_MS[AVAILABILITY_INTERVALS_MS.length - 1];
      if (intervalIndex < AVAILABILITY_INTERVALS_MS.length - 1) intervalIndex++;
      console.warn(`[llm] ${label}: unavailable (503/429). Waiting ${waitMs / 1000}s before retrying...`);
      await delay(waitMs);
    }
  }
}

/**
 * Try a Gemini operation across the model chain: 429 (quota) advances to the
 * next model immediately; 503 retries the same model with escalation.
 */
async function geminiWithChain<T>(
  label: string,
  fn: (model: string) => Promise<T>,
): Promise<T> {
  const models = geminiModelChain();
  for (const model of models) {
    try {
      return await withAvailability503(`${label}/${model}`, () => fn(model));
    } catch (err) {
      if (is429(err)) {
        console.warn(`[llm] ${label}/${model}: 429 quota/rate limit. Trying next model...`);
        continue;
      }
      throw err;
    }
  }
  // All models exhausted: signal 429 so the outer withAvailability waits and
  // retries the whole chain (quota resets daily).
  throw new Error(`All Gemini ${label} models exhausted quota (429)`);
}

async function buildPrompt(
  post: NewPost,
  feedback?: string,
  imageDescription?: string,
): Promise<{ prompt: string; instructionIds: string[] }> {
  const base = `Write a high-quality article in castilian spanish of about 500 words for a wild posting (pegada de carteles)/street marketing website based on this Instagram post.

The post is about job that the company has done, so the article must describe the job that has been done with the aim of attracting new clients.

Instagram caption: ${post.caption}

Instagram image URL: ${post.mediaUrl}

Respond EXCLUSIVELY with a JSON object. Use standard JSON: double quotes for all keys and string values; if a string value contains a work title, escape its quotes as \\" inside the JSON string.

Follow this IMPORTANT writing style rule:

1. Colloquial and friendly style, natural and realistic, without adverbs or adjectives that feel artificial or wouldn't come up in everyday conversation

CRITICAL — NO INVENTING (applies to title, description, AND content): Never fabricate or invent details that are not explicitly present in the Instagram caption or image. Do not invent specific dates, numbers, names of districts, locations, quantity of posters/carteles, or any other concrete detail not visible in the caption or the image. Base the entire article ONLY on what the caption and the image actually show. If they are vague, describe the type of work professionally and generically without making up specific details. Every concrete claim must trace back to the caption or the image.

GRAMMAR RULE (RAE) (applies to title, description, AND content): Titles of works (albums, tours, events, songs, movies) MUST be enclosed in ESCAPED DOUBLE QUOTES (\\"Las Mujeres Ya No Lloran World Tour\\"), NEVER single quotes. Use the official capitalization as promoted by the artist or brand if it is widely known (e.g. \\"Las Mujeres Ya No Lloran World Tour\\"). If you are unsure of the official casing of a work, write it in lowercase within escaped double quotes (e.g. \\"Las mujeres ya no lloran world tour\\"). Never capitalize every word of a title unless it matches the official format.

Follow these STRICT SEO rules:

1. title: A complete, catchy, SEO-optimized sentence in Castilian Spanish. Maximum 120 characters. The title MUST be content-related — use the Instagram caption and image to include specific, concrete details about the campaign, client, event or city so the title is unique and descriptive, never generic (e.g. GOOD: "Cómo conquistamos Madrid con la pegada de carteles para la gira europea de Shakira" — BAD: "Cómo conquistamos Madrid con la pegada de carteles"). The title must be a self-contained, grammatically complete phrase — it must NEVER end with a preposition, article, conjunction or dangling word such as "de", "del", "de la", "de los", "de las", "para", "con", "en", "por", "a", "al", "la", "el", "los", "las", "un", "una", "y", "que". If a work title appears in the title, use escaped double quotes (\\"...\\").

2. description: A professional summary. It MUST NOT start with the same words as the title. Any work title (album, tour, event) mentioned in the description MUST be in ESCAPED DOUBLE QUOTES (\\"...\\") following the RAE rule with correct capitalization. Example: "Te contamos cómo ejecutamos la pegada de carteles para la gira \\"Las Mujeres Ya No Lloran World Tour\\" de Shakira".

3. content: Full Markdown article with a clear structure for SEO: start with a short introductory paragraph, then 2-3 sections each with a level-2 heading (## heading, never #), and include at least one bulleted list (-) or numbered list (1.) somewhere in the article. If the post is about a "pegada de carteles" job, include the keyword "pegada de carteles" twice. Work titles (albums, tours, shows, events, songs) in the content must be written between GUILLEMETS, like this: «Las Mujeres Ya No Lloran World Tour». Never use double quotes, escaped quotes, asterisks or single quotes around work titles in the content — the system converts guillemets to italic text with double quotes automatically. Use **bold** for the main SEO keywords: bold "pegada de carteles" and "street marketing" at their first natural mention (max 2-3 bold phrases per article so it stays readable).

4. tags: 5-7 lowercase tags.

5. category: One category.  CRITICAL: The title and the first sentence of the description must be completely different.

Example:
Bad: Title "Publicidad en Madrid", Description "Publicidad en Madrid es..."
Good: Title "Cómo revolucionamos la publicidad en Madrid", Description "Descubre la técnica de pegada de carteles que..."

IMPORTANT: The NO INVENTING rule and the RAE title-formatting rule above apply to ALL output fields: title, description, and content. The description must also use escaped double quotes around work titles and must not fabricate details. The content must also respect both rules throughout the entire article.

SELF-AUDIT BEFORE RETURNING THE JSON: Check that every work title (album, tour, event, song, movie) in the title and description fields is enclosed in ESCAPED DOUBLE QUOTES (\\"Las Mujeres Ya No Lloran World Tour\\"). If any work title is missing escaped double quotes in those fields, fix it before returning. Never use single quotes for work titles in title or description. In the content field, check that every work title is between GUILLEMETS («Las Mujeres Ya No Lloran World Tour») — never double quotes, escaped quotes or asterisks in the content for work titles. Also check the content has 2-3 level-2 headings (##), at least one list, and bolded SEO keywords (**pegada de carteles**, **street marketing**) at their first mention. Fix any violation before returning the JSON.`;

  const instructions = await loadInstructions();
  const guidelines = formatInstructions(instructions);
  const instructionIds = instructions.map((i) => i.id);
  let prompt = base;

  if (imageDescription) {
    prompt += `\n\nIMAGE ANALYSIS (what is actually visible in the Instagram image, described by a vision model):\n${imageDescription}\n\nUse this image analysis to make the article more accurate, realistic and creative, but NEVER invent details that are not visible in the image or the caption.`;
  }

  if (guidelines) {
    prompt += `\n\nReviewer guidelines that MUST be followed for this article:\n${guidelines}`;
  }

  if (feedback) {
    prompt += `\n\nIMPORTANT: The user reviewed your previous output and requested these changes:\n${feedback}\n\nRegenerate the article addressing this feedback while keeping all other rules intact.`;
  }

  return { prompt, instructionIds };
}

function parseArticle(raw: string): LlmArticle {
  const cleaned = raw.replace(/```json\n?|\n?```/g, "").trim();
  const data = JSON.parse(cleaned);
  const article = LlmArticleSchema.parse(data);

  // Normalize work-title formatting for BOTH providers (deterministic):
  // - content:   «Título»  ->  *"Título"*   (italic Markdown + double quotes)
  // - title/desc: «Título» ->  "Título"     (plain quotes, no markdown)
  // - fix stray backslash-quotes some models emit inside JSON strings
  const fixEscapes = (s: string) => s.replace(/\\"/g, '"');
  article.content = fixEscapes(article.content).replace(/«([^»]+)»/g, '*"$1"*');
  article.title = fixEscapes(article.title).replace(/«([^»]+)»/g, '"$1"');
  article.description = fixEscapes(article.description).replace(/«([^»]+)»/g, '"$1"');
  return article;
}

/**
 * Generic single-message completion for NON-article outputs (e.g. tweets).
 * Uses the same providers/clients with availability + retry handling.
 * Returns the raw text content (no schema parsing).
 */
export async function generateTextCompletion(
  prompt: string,
  provider: LlmProvider,
  opts: { temperature?: number; maxTokens?: number } = {},
): Promise<string> {
  const temperature = opts.temperature ?? 0.7;
  const maxTokens = opts.maxTokens ?? 2048;

  const run = (): Promise<string> => {
    if (provider === "gemini") {
      return geminiWithChain("Gemini", async (model) => {
        const generativeModel = gemini.getGenerativeModel({
          model,
          generationConfig: {
            temperature,
            maxOutputTokens: maxTokens,
            responseMimeType: "text/plain",
          },
        });
        const result = await generativeModel.generateContent(prompt);
        const raw = result.response.text();
        if (!raw) throw new Error("Gemini returned empty response");
        return raw;
      });
    }
    return withRetries(
      () =>
        deepseekClient.chat.completions
          .create({
            model: DEEPSEEK_MODEL,
            messages: [
              { role: "system", content: "You are an expert Spanish copywriter for a street marketing company." },
              { role: "user", content: prompt },
            ],
            temperature,
            max_tokens: maxTokens,
          })
          .then((c) => {
            const raw = c.choices[0]?.message?.content;
            if (!raw) throw new Error("DeepSeek returned empty response");
            return raw;
          }),
      "DeepSeek",
      "text",
    );
  };

  return withAvailability(provider === "gemini" ? "Gemini" : "DeepSeek", run);
}

/**
 * Gemini completion WITH Google Search grounding — answers questions that need
 * live web knowledge (e.g. "¿Cuál es la cuenta oficial en X de Trueno?").
 * Uses gemini-2.5-flash (verified to support google_search on this key).
 */
export async function groundedCompletion(
  prompt: string,
  opts: { temperature?: number; maxTokens?: number } = {},
): Promise<string> {
  const temperature = opts.temperature ?? 0.2;
  const maxTokens = opts.maxTokens ?? 500;

  return withAvailability("Gemini", () =>
    geminiWithChain("Gemini", async (model) => {
      const generativeModel = gemini.getGenerativeModel({
        model,
        generationConfig: {
          temperature,
          maxOutputTokens: maxTokens,
          responseMimeType: "text/plain",
        },
        tools: [{ googleSearch: {} } as never],
      });
      const result = await generativeModel.generateContent(prompt);
      const raw = result.response.text();
      if (!raw) throw new Error("Grounded Gemini returned empty response");
      return raw;
    }),
  );
}

async function fetchImageBase64(url: string): Promise<{ base64: string; mimeType: string } | null> {
  try {
    const res = await fetch(url);
    if (!res.ok) {
      console.warn(`[llm] image fetch failed: ${res.status} for ${url.slice(0, 80)}`);
      return null;
    }
    const contentType = res.headers.get("content-type") ?? "image/jpeg";
    if (!contentType.startsWith("image/")) {
      console.warn(`[llm] media URL is not an image (${contentType}), skipping image context`);
      return null;
    }
    const buf = Buffer.from(await res.arrayBuffer());
    return { base64: buf.toString("base64"), mimeType: contentType };
  } catch (err) {
    console.warn(`[llm] image fetch error: ${(err as Error).message}`);
    return null;
  }
}

async function callDeepseek(post: NewPost, feedback?: string, imageDescription?: string): Promise<LlmArticle> {
  const { prompt, instructionIds } = await buildPrompt(post, feedback, imageDescription);

  const completion = await deepseekClient.chat.completions.create({
    model: DEEPSEEK_MODEL,
    messages: [
      { role: "system", content: "You are an expert Spanish SEO copywriter for a street marketing company." },
      { role: "user", content: prompt },
    ],
    temperature: 0.7,
    max_tokens: 16_000,
    response_format: { type: "json_object" },
  });

  const raw = completion.choices[0]?.message?.content;
  if (!raw) throw new Error("DeepSeek returned empty response");
  const article = parseArticle(raw);
  if (instructionIds.length > 0) await markInstructionsApplied(instructionIds);
  return article;
}

async function callGemini(
  model: string,
  post: NewPost,
  feedback?: string,
  imageDescription?: string,
): Promise<LlmArticle> {
  const { prompt, instructionIds } = await buildPrompt(post, feedback, imageDescription);

  const generativeModel = gemini.getGenerativeModel({
    model,
    generationConfig: {
      temperature: 0.7,
      maxOutputTokens: 16_000,
      responseMimeType: "application/json",
    },
  });

  // Gemini supports images: attach the Instagram image so it can see it directly.
  const image = await fetchImageBase64(post.mediaUrl);

  const parts: (string | { inlineData: { mimeType: string; data: string } })[] = [prompt];
  if (image) {
    parts.push({
      inlineData: { mimeType: image.mimeType, data: image.base64 },
    });
  }

  const result: GenerateContentResult = await generativeModel.generateContent(parts);
  const raw = result.response.text();
  if (!raw) throw new Error("Gemini returned empty response");
  const article = parseArticle(raw);
  if (instructionIds.length > 0) await markInstructionsApplied(instructionIds);
  return article;
}

async function withRetries<T>(
  fn: () => Promise<T>,
  label: string,
  postId: string,
): Promise<T> {
  let lastError: unknown;
  for (let attempt = 1; attempt <= MAX_RETRIES; attempt++) {
    try {
      return await fn();
    } catch (err) {
      if (is503(err)) throw err; // handled by withAvailability
      lastError = err;
      console.warn(`[llm] ${label} attempt ${attempt}/${MAX_RETRIES} failed for post ${postId}:`, (err as Error).message);
      if (attempt < MAX_RETRIES) await delay(BASE_DELAY_MS * attempt);
    }
  }
  throw new Error(`${label} generation failed after ${MAX_RETRIES} attempts: ${(lastError as Error).message}`);
}

/**
 * Have Gemini (vision) describe what is actually visible in the Instagram image.
 * Cached per post id within the process. Uses the model chain; waits for Gemini
 * if it is 503.
 */
export async function describeImage(post: NewPost): Promise<string> {
  const cached = imageDescriptionCache.get(post.id);
  if (cached !== undefined) return cached;

  const description = await withAvailability("describeImage", () =>
    geminiWithChain("describeImage", async (model) => {
      const image = await fetchImageBase64(post.mediaUrl);
      if (!image) return "";

      const generativeModel = gemini.getGenerativeModel({
        model,
        generationConfig: {
          temperature: 0.2,
          maxOutputTokens: 1024,
          responseMimeType: "text/plain",
        },
      });

      const result = await generativeModel.generateContent([
        `Describe in Spanish, factually and in detail, what is actually visible in this Instagram image: the subject(s), people, brands, text, colors, location hints, mood and atmosphere. ONLY describe what you can see. Do not invent anything that is not visible.`,
        { inlineData: { mimeType: image.mimeType, data: image.base64 } },
      ]);
      return result.response.text().trim();
    }),
  );

  imageDescriptionCache.set(post.id, description);
  return description;
}

export function generateDeepseekArticle(post: NewPost, feedback?: string, imageDescription?: string): Promise<LlmArticle> {
  return withAvailability("DeepSeek", () =>
    withRetries(() => callDeepseek(post, feedback, imageDescription), "DeepSeek", post.id),
  );
}

export function generateGeminiArticle(post: NewPost, feedback?: string, imageDescription?: string): Promise<LlmArticle> {
  return withAvailability("Gemini", () =>
    geminiWithChain("Gemini", (model) =>
      withRetries(() => callGemini(model, post, feedback, imageDescription), `Gemini/${model}`, post.id),
    ),
  );
}

/**
 * Generate with the chosen provider only (used for regeneration after feedback).
 * Defaults to gemini (primary provider), falls back to deepseek.
 * The Instagram image is read (via Gemini vision) and passed to both providers.
 */
export async function generateArticle(
  post: NewPost,
  feedback?: string,
  provider?: LlmProvider,
): Promise<LlmArticle> {
  const imageDescription = await describeImage(post);
  if (provider === "deepseek") {
    return generateDeepseekArticle(post, feedback, imageDescription);
  }
  return generateGeminiArticle(post, feedback, imageDescription);
}

/**
 * Generate with BOTH providers in parallel. Each retries its own 503s until
 * available, so both must succeed before this returns.
 * Gemini reads the actual image (vision); DeepSeek receives Gemini's image
 * description as text.
 */
export async function generateArticles(
  post: NewPost,
  feedback?: string,
): Promise<{ gemini?: LlmArticle; deepseek?: LlmArticle }> {
  // 1) Gemini reads the image first — this also waits for Gemini if it is 503.
  const imageDescription = await describeImage(post);

  // 2) Generate with both providers in parallel. Each independently waits for
  //    its own provider to be available (503 handling).
  const [geminiResult, deepseekResult] = await Promise.allSettled([
    generateGeminiArticle(post, feedback, imageDescription),
    generateDeepseekArticle(post, feedback, imageDescription),
  ]);

  const articles: { gemini?: LlmArticle; deepseek?: LlmArticle } = {};
  if (geminiResult.status === "fulfilled") {
    articles.gemini = geminiResult.value;
  } else {
    console.warn(`[llm] Gemini failed for post ${post.id}:`, (geminiResult.reason as Error).message);
  }
  if (deepseekResult.status === "fulfilled") {
    articles.deepseek = deepseekResult.value;
  } else {
    console.warn(`[llm] DeepSeek failed for post ${post.id}:`, (deepseekResult.reason as Error).message);
  }

  if (!articles.gemini && !articles.deepseek) {
    const gErr = geminiResult.status === "rejected" ? (geminiResult.reason as Error).message : "unknown";
    const dErr = deepseekResult.status === "rejected" ? (deepseekResult.reason as Error).message : "unknown";
    throw new Error(`Both LLMs failed for post ${post.id}: gemini=${gErr}, deepseek=${dErr}`);
  }

  return articles;
}
