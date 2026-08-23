# Urban Style Instagram-to-Blog — Session Handoff

> Written 2026-08-14. Read this fully + `AGENTS.md` at the start of any new session.

## 1. What this system does

Automated content pipeline: Instagram posts → AI-generated blog articles (Astro/MDX) → Telegram approval → publish to production site (FTPS) → social posting (X + Facebook via Buffer; GBP next).

- All code lives in: `scripts/instagram-to-blog/` (TypeScript, run with **Bun**)
- Project root: `/home/alvdev/dev/www/web-projects/urbanstyle/astro`
- Server (production pipeline + future): `kv55.local` (192.168.1.132), project at `~/dev/urban`, SSH key `~/.ssh/urban-kv55` (`ssh -i ~/.ssh/urban-kv55 alvdev@kv55.local`)

## 2. Modules

| File | Role |
|------|------|
| `index.ts` | Daily sync: fetch IG, queue 1 unprocessed post (dual LLM articles), send Telegram picker. Fixed stop boundary `IG_STOP_POST_ID` + excludes pending/skipped/published IDs |
| `watcher.ts` | Hourly: only when `backlogDone=true` AND no pending — detects truly new IG posts (id > highestSeenId) → queue |
| `bot.ts` | Telegram bot (grammy). Callbacks: dual-LLM combo pick (`mix:`), approve/reject/feedback, crop (📐 top/center/bottom), manual edits (✏️ title/desc/content), remove/redes after publish, tweet flow, FB flow (`pickFb`/`useFbPage`/`fixFb`/`editFb`/`rejectFb`), publish helpers (`publishToX`/`publishToFb`, server-side gates), postX/postFb safety wrappers, useHandle, fixTweet. Publish only via ▶️ Publicar en redes after both texts approved. Uses `freshState()` (bypasses cache — critical) |
| `state.ts` | `.pending.json` read/write (shared across processes). Fields: pending[], published[], skippedIds[], lastProcessedId, highestSeenId, tokenExpiresAt, backlogDone, chatId |
| `types.ts` | Zod schemas + interfaces (PendingEntry, PublishedEntry with per-platform `social: {x?, facebook?, gbp?}`, LlmProvider, etc.) |
| `llm.ts` | Gemini (primary, model chain 3.6→3.5→2.5-flash for quota 429) + DeepSeek (opencode-go `deepseek-v4-flash`). `generateArticles` (dual), `generateTextCompletion` (raw text), `groundedCompletion` (Google Search grounding), 503/429 availability retry (30s→60s→120s), describeImage (Gemini vision), guillemets «» → italic `*"..."*` normalization |
| `tweet.ts` | `buildTweetPrompt(post, caption, postTimestamp)` — IG-caption-based tweet, style examples, RAE quotes, Spain-handle rule, past-tense detection (`shouldUsePastTense`: >1 week old OR caption date in past). `generateTweets` appends URL (23-char t.co budget, body ≤257), strips URLs LLM sneaks in |
| `content.ts` | `preparePost`, `buildMdx` (frontmatter incl. `objectPosition`), `writePostFiles`, `generateSlug` (max 100, word-boundary, no dangling prepositions), `validateTitleEnding` |
| `deploy.ts` | `buildSite` (bun run build, NODE_BIN_DIR), `uploadDist` (SHA-256 hash manifest `.deploy-manifest.json`, curl FTPS, cert-pinned), `removeRemoteDir` (basic-ftp, accepts `/blog/slug` or absolute) |
| `mailer.ts` | nodemailer alerts → ALERT_EMAIL via SMTP |
| `telegram.ts` | Notifications (approval-dual, approval, published, error), keyboards, `escMarkdown`, `mdToHtml` (Markdown→HTML for collapsible `<blockquote expandable>`), collapsible articles (ONE blockquote per article) |
| `social/buffer.ts` | Buffer GraphQL client: `getXChannel()`/`getFbChannel()` (service lookup), `createPost(channelId, text, imageUrl?, facebookType?, facebookAnnotations?)` with `mode:"shareNow"`, returns externalLink |
| `social/facebook.ts` | Direct Facebook Graph API (own page token): `createFbPost(message, link)` (link preview + API deletion), `deleteFbPost`, `resolvePageId` (needs app review for non-owned pages). Real page mentions NOT supported by FB API |
| `social/xverify.ts` | `verifyHandle` (fetch x.com profile, parse followers + is_blue_verified), `findOfficialHandle` (web search Bing+DDG FIRST via shared `searchProfile`, then grounded Gemini as fallback; Spain-account rule; candidate verified by scraping x.com), `suggestCandidates`, `extractHandles`, `verifyTweetHandles` |
| `social/fbpost.ts` | FB post text generation: `buildFbPrompt` (tweet-adapted, no 280 limit, 2-3 sentences, RAE + Spain rules, past-tense detection), `generateFbTexts` (dual Gemini/DeepSeek, URL appended in code) |
| `social/websearch.ts` | Web-search for official-account lookups. Chain: **Bing** (direct, decodes `u=a1`) → **DuckDuckGo HTML** (direct, throttled 4s, 8s timeout) → **Brave HTML via `WEBSEARCH_PROXY`** (ONLY last fallback when no usable result for the host filter; PacketStream residential). Circuit breaker per engine (3 fails → skip 10 min); usable results prioritized. Shared `searchProfile`, `buildSearchQueries`, `entityPhraseForHandle`, `sentenceForEntity`, `textsRelate` |
| `social/fbverify.ts` | `suggestFbPages(text, caption)` — per @mention official FB page lookup: web search FIRST (`searchProfile`: handle-scoped + caption ENTITY-PHRASE natural-language queries "X facebook página oficial" via `entityPhraseForHandle`; Spain variants; URL-derived user) with relation guard (`fbPageRelatesToHandle`), then **grounded Gemini as fallback** (same prompt system as X, Spain rule). Result carries `source: "web" | "gemini"`. No suggestion → ✂️/✏️ only (no FB-search links) |

## 3. Key rules (prompt/UX constraints)

- **RAE**: work titles in double quotes; guillemets «» in LLM output → converted to `*"…"*` (content) / `"…"` (title/desc)
- **NO INVENTING**: only facts from IG caption/image
- **Tweets**: total ≤280 chars (URL = 23 t.co), body ≤257, URL appended in code; past tense if post >1 week or event date passed; **Spain account** if multiple country accounts; @handles verified (grounded lookup + ✅ Usar button); ✂️ Auto-arreglar regenerates without invalid mentions; handles of artists AND venues/brands required in prompt (e.g. "Movistar Arena" → @movistararenaes); manual mention replacement fallback via `✏️ Editar menciones` (`@viejo → @nuevo`)
- **Slug**: ≤100 chars, word-boundary truncation, no trailing preposition
- **Social gating**: nothing publishes while mentions are unresolved — X: every @handle scrapes as `verified` (xverify); FB: no @handles left in the text (replaced via ✅ Usar or removed via ✂️). Readiness re-checked in the `social:` handler before the combined publish; `publishToX`/`publishToFb` enforce server-side. ▶️ Publicar en redes is the only publish trigger.
- **Header image**: `crop` prop on blog post Header (1920×810 sharp crop, `objectPosition` top/center/bottom)
- **Keyboard layout**: 2×2 for `[📤 Publicar][✏️ Editar] / [❌ Rechazar][✅ Usar]`; long combo buttons 1/row; short pairs 1 row of 2

## 4. Telegram flow (end user)

Blog picker → combo pick → crop → approve → publish (build+FTPS) → `🗑 Eliminar` / `▶️ Publicar en redes` → prepare X (tweet picker → pick → handle verification `✅ Usar @official` / ✂️ Auto-arreglar) → once the tweet is clean the bot auto-continues → prepare FB (same approved tweet text → grounded page suggestions `✅ Usar [page]` / ✂️ Quitar menciones) → "✅ Textos de X y Facebook aprobados — pulsa ▶️ Publicar en redes para publicar" → ▶️ re-checks readiness → publishes both via Buffer (X then FB, one progress message). No per-platform publish buttons; ▶️ is the only publish trigger.

## 5. Environment (.env at project root)

- Instagram: `IG_ACCESS_TOKEN` (expires — alert flow), `IG_APP_ID`, `IG_APP_SECRET`, `IG_STOP_POST_ID` (18064397023917410), `IG_FIRST_RUN_STOP_ID`
- LLM: `OPENCODE_API_KEY` (opencode-go), `OPENCODE_BASE_URL=https://opencode.ai/zen/go/v1`, `OPENCODE_MODEL=deepseek-v4-flash`, `GEMINI_API_KEY`, `GEMINI_MODEL=gemini-3.6-flash`, `GEMINI_MODELS=gemini-3.6-flash,gemini-3.5-flash,gemini-2.5-flash`
- Telegram: `TELEGRAM_BOT_TOKEN`, `TELEGRAM_CHAT_ID=1345910809`
- Buffer: `BUFFER_ACCESS_TOKEN`, `BUFFER_ORGANIZATION_ID=6621306828ef537f6f59f9ff`
- Deploy: `FTP_HOST=ftp.urbanstylepublicity.com`, `FTP_USER=alvdev`, `FTP_PASSWORD`, `FTP_REMOTE_PATH=/urbanstylepublicity.com`, `NODE_BIN_DIR=/home/alvdev/.nvm/versions/node/v22.23.2/bin`
- SMTP: `SMTP_HOST=mail.empiric.studio`, `SMTP_USER=urban@empiric.studio`, `SMTP_PASSWORD`, `ALERT_EMAIL=alvdev@outlook.com`
- X removed (Camoufox deleted): no `X_USERNAME` etc.

## 6. Buffer channels (discovered, ids stable)

| Channel | id |
|---------|-----|
| X/Twitter @pegadacarteles | `66213183f1ac4a3c942d04c4` |
| Facebook "Urban Style Publicity" | `662130b9f1ac4a3c941ff00a` |
| YouTube | `662131f7f1ac4a3c94344a34` |
| StartPage | `662132c54ed7b60c24f0b77b` |

## 7. Buffer GraphQL cheat sheet

- Endpoint: `https://api.buffer.com/graphql`, header `Authorization: Bearer <token>`
- Channels: `{ channels(input:{organizationId}) { id name service } }`
- Create post: mutation `createPost(input:{ channelId, text, mode:"shareNow", schedulingType:"automatic", assets:[{image:{url}}] })` — returns UNION `PostActionPayload`, use `... on PostActionSuccess { post { id externalLink } }`
- Note: `mode` not `shareMode`; `assets[].image.url` (Buffer fetches server-side); posts via Buffer are NEVER 403-blocked (only our x.com verification fetches are)
- **Facebook REQUIRES** `metadata: { facebook: { type } }` (`PostTypeFacebook`: post | story | reel) — without it Buffer errors "Facebook posts require a type". Auto-chosen by IG media type in `postFb`: `VIDEO` → `reel`, else `post`. X needs no metadata.
- **FB page mentions (concluded 2026-08-17):** NOT possible via any API path. Tested: Buffer annotations (only `@[url]`), direct Graph API `message_tags` (ignored on create), `@[page-id]`/`@[id:name]` in message (stripped/not converted), `tags` (blocked), `AnnotationType.mention` (output-only). Facebook only allows mentioning other pages from its composer UI. `postFb` publishes via **direct Graph API** (`social/facebook.ts`, `FB_ACCESS_TOKEN`/`FB_PAGE_ID`, own Meta app) when configured — benefits: real `link` preview (og:image) + API deletion — else falls back to Buffer. No mention machinery in state.

## 8. Systemd (dev machine, user units)

- `instagram-bot.service` (bot.ts), `instagram-to-blog.timer` (daily 11:00-12:00 Europe/Madrid + RandomizedDelaySec=3600), `instagram-watcher.timer` (hourly). Units in `systemd/`. **Remove `User=` lines** in user units (group errors otherwise).

## 9. Known gotchas

- Telegram legacy Markdown: escape `_ * [ ] ` ` in user/LLM text (`escMarkdown`); image URLs (`_astro`) MUST be escaped or "can't parse entities" 400
- Telegram 4096 limit: collapsible blockquotes must self-cap (trim near END, never mid-`</blockquote>`)
- X verification: fetch may 403/429 → retry ×3 backoff → `⚠️ no verificado`
- IG media_url expires (~5 days) — use og:image from built page for Buffer instead
- Gemini free tier: 20 req/day per model → model chain fallback essential; grounding uses 2.5-flash; `withAvailability` is BOUNDED (2 retry cycles) so a fully quota-exhausted day fails fast → callers fall back to DeepSeek / repair path instead of blocking forever
- DeepSeek reasoning model: `max_tokens` must be ≥4000 or `content` comes back empty

## 10. Facebook publishing (implemented 2026-08-14)

- FB page channel id: `662130b9f1ac4a3c941ff00a` (discovered via `getFbChannel()`, service "facebook")
- User decisions: ▶️ Publicar en redes prepares BOTH platforms, then publishes only after mentions+texts are approved; FB text = the exact approved X tweet (X rules apply by construction); FB mentions verified via grounded Gemini lookup (FB blocks scraping) — same prompt system as X's `findOfficialHandle`
- Verification gating (both platforms): nothing is published while mentions are unresolved — X: every handle scrapes as `verified` (`xverify`); FB: zero @handles remain in the text (each replaced with the official page name via ✅ Usar, or removed via ✂️). The `social:` handler re-checks readiness (server-side) before the combined publish; `publishToX`/`publishToFb` helpers enforce the same gates.
- Flow: ▶️ Publicar en redes → readiness check → not ready: `startTweetFlow` (dual tweet picker → `pickTweet` → xverify handle check → ✅ Usar/✂️) → tweet clean → auto-advance `startFbFlow` (FB text = approved tweet verbatim; `social/fbverify.ts` `findOfficialFbPage`, Google Search grounding, Spain rule → `✅ Usar [page]` / ✂️ Quitar menciones / ✏️ / ❌) → "✅ Textos aprobados — pulsa ▶️" → ready: combined publish X then FB (`publishToX` → `publishToFb`, one progress message)
- No per-platform publish buttons: `postX`/`postFb` exist only as safety wrappers for stale keyboards; the only publish trigger is ▶️ Publicar en redes
- State: reuses `social.x` and `social.facebook` slots as-is (`tweet` field holds the FB text); suggestions kept in bot session `fbSuggestions[postId]` indexed by button (FB page names can exceed Telegram's 64-byte callback_data limit); `PublishedEntry.mediaType` (from IG `media_type`) drives the FB post type (VIDEO→reel, else post)
- Callbacks: `pickFb:`, `postFb:`, `editFb:`, `rejectFb:`, `useFbPage:`, `fixFb:`
