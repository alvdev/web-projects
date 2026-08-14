# Urban Style Instagram-to-Blog — Session Handoff

> Written 2026-08-14. Read this fully + `AGENTS.md` at the start of any new session.

## 1. What this system does

Automated content pipeline: Instagram posts → AI-generated blog articles (Astro/MDX) → Telegram approval → publish to production site (FTPS) → social posting (X via Buffer; Facebook/GBP next).

- All code lives in: `scripts/instagram-to-blog/` (TypeScript, run with **Bun**)
- Project root: `/home/alvdev/dev/www/web-projects/urbanstyle/astro`
- Server (production pipeline + future): `kv55.local` (192.168.1.132), project at `~/dev/urban`, SSH key `~/.ssh/urban-kv55` (`ssh -i ~/.ssh/urban-kv55 alvdev@kv55.local`)

## 2. Modules

| File | Role |
|------|------|
| `index.ts` | Daily sync: fetch IG, queue 1 unprocessed post (dual LLM articles), send Telegram picker. Fixed stop boundary `IG_STOP_POST_ID` + excludes pending/skipped/published IDs |
| `watcher.ts` | Hourly: only when `backlogDone=true` AND no pending — detects truly new IG posts (id > highestSeenId) → queue |
| `bot.ts` | Telegram bot (grammy). Callbacks: dual-LLM combo pick (`mix:`), approve/reject/feedback, crop (📐 top/center/bottom), manual edits (✏️ title/desc/content), remove/redes after publish, tweet flow, postX (Buffer), useHandle, fixTweet. Uses `freshState()` (bypasses cache — critical) |
| `state.ts` | `.pending.json` read/write (shared across processes). Fields: pending[], published[], skippedIds[], lastProcessedId, highestSeenId, tokenExpiresAt, backlogDone, chatId |
| `types.ts` | Zod schemas + interfaces (PendingEntry, PublishedEntry with per-platform `social: {x?, facebook?, gbp?}`, LlmProvider, etc.) |
| `llm.ts` | Gemini (primary, model chain 3.6→3.5→2.5-flash for quota 429) + DeepSeek (opencode-go `deepseek-v4-flash`). `generateArticles` (dual), `generateTextCompletion` (raw text), `groundedCompletion` (Google Search grounding), 503/429 availability retry (30s→60s→120s), describeImage (Gemini vision), guillemets «» → italic `*"..."*` normalization |
| `tweet.ts` | `buildTweetPrompt(post, caption, postTimestamp)` — IG-caption-based tweet, style examples, RAE quotes, Spain-handle rule, past-tense detection (`shouldUsePastTense`: >1 week old OR caption date in past). `generateTweets` appends URL (23-char t.co budget, body ≤257), strips URLs LLM sneaks in |
| `content.ts` | `preparePost`, `buildMdx` (frontmatter incl. `objectPosition`), `writePostFiles`, `generateSlug` (max 100, word-boundary, no dangling prepositions), `validateTitleEnding` |
| `deploy.ts` | `buildSite` (bun run build, NODE_BIN_DIR), `uploadDist` (SHA-256 hash manifest `.deploy-manifest.json`, curl FTPS, cert-pinned), `removeRemoteDir` (basic-ftp, accepts `/blog/slug` or absolute) |
| `mailer.ts` | nodemailer alerts → ALERT_EMAIL via SMTP |
| `telegram.ts` | Notifications (approval-dual, approval, published, error), keyboards, `escMarkdown`, `mdToHtml` (Markdown→HTML for collapsible `<blockquote expandable>`), collapsible articles (ONE blockquote per article) |
| `social/buffer.ts` | Buffer GraphQL client: `getXChannel()`, `createPost(channelId, text, imageUrl?)` with `mode:"shareNow"`, returns externalLink |
| `social/xverify.ts` | `verifyHandle` (fetch x.com profile, parse followers + is_blue_verified), `findOfficialHandle` (grounded Gemini, Spain-account rule), `suggestCandidates`, `extractHandles`, `verifyTweetHandles` |

## 3. Key rules (prompt/UX constraints)

- **RAE**: work titles in double quotes; guillemets «» in LLM output → converted to `*"…"*` (content) / `"…"` (title/desc)
- **NO INVENTING**: only facts from IG caption/image
- **Tweets**: total ≤280 chars (URL = 23 t.co), body ≤257, URL appended in code; past tense if post >1 week or event date passed; **Spain account** if multiple country accounts; @handles verified (grounded lookup + ✅ Usar button); ✂️ Auto-arreglar regenerates without invalid mentions
- **Slug**: ≤100 chars, word-boundary truncation, no trailing preposition
- **Header image**: `crop` prop on blog post Header (1920×810 sharp crop, `objectPosition` top/center/bottom)
- **Keyboard layout**: 2×2 for `[📤 Publicar][✏️ Editar] / [❌ Rechazar][✅ Usar]`; long combo buttons 1/row; short pairs 1 row of 2

## 4. Telegram flow (end user)

Blog picker → combo pick → crop → approve → publish (build+FTPS) → `🗑 Eliminar` / `▶️ Publicar en redes` → tweet picker (Gemini/DeepSeek) → pick → handle verification → `✅ Usar @official` or ✂️ → 📤 Publicar en X (Buffer) → confirmation with real X URL.

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

## 8. Systemd (dev machine, user units)

- `instagram-bot.service` (bot.ts), `instagram-to-blog.timer` (daily 11:00-12:00 Europe/Madrid + RandomizedDelaySec=3600), `instagram-watcher.timer` (hourly). Units in `systemd/`. **Remove `User=` lines** in user units (group errors otherwise).

## 9. Known gotchas

- Telegram legacy Markdown: escape `_ * [ ] ` ` in user/LLM text (`escMarkdown`); image URLs (`_astro`) MUST be escaped or "can't parse entities" 400
- Telegram 4096 limit: collapsible blockquotes must self-cap (trim near END, never mid-`</blockquote>`)
- X verification: fetch may 403/429 → retry ×3 backoff → `⚠️ no verificado`
- IG media_url expires (~5 days) — use og:image from built page for Buffer instead
- Gemini free tier: 20 req/day per model → model chain fallback essential; grounding uses 2.5-flash
- DeepSeek reasoning model: `max_tokens` must be ≥4000 or `content` comes back empty

## 10. Next phase: Facebook publishing

- Buffer FB page channel id: `662130b9f1ac4a3c941ff00a` (already connected)
- Reuse `createPost(channelId, text, imageUrl?)`; Facebook has no 280 limit (2-3 sentences + link fine)
- Open questions to confirm with user:
  1. After publish → ▶️ Publicar en redes: platform selector `[X] [Facebook] [Ambos]` or sequential separate approvals?
  2. FB post content: short (tweet-adapted) or longer post?
  3. FB @mentions verification: FB blocks scraping — skip verification or rely on LLM + manual edit?
  4. State schema already has `social.facebook` slot
