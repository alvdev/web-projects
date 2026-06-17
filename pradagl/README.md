# Prada GL — Astro + VanillaJS + Tailwind

Single-page site for a fictitious logistics company. Built with **Astro 5** (Node SSR adapter), **Tailwind CSS v4** (via `@tailwindcss/vite`), and plain VanillaJS form handling on the client.

The contact form posts to `/api/contact`, which:

1. Validates the four required fields (`name`, `company`, `email`, `phone`).
2. Verifies the **Cloudflare Turnstile** token server-side (invisible widget).
3. Sends a templated email via **Nodemailer** with SMTP authentication.

## Stack

| Layer | Choice |
|------|--------|
| Framework | Astro 5 (`output: 'server'`, `@astrojs/node` standalone) |
| Styling | Tailwind v4 (`@tailwindcss/vite`) |
| Forms | VanillaJS — no React / Vue / Svelte |
| Email | Nodemailer 6 (SMTP) |
| Anti-bot | Cloudflare Turnstile (invisible) |

Colors: brand **red** (`#e63946`) and brand **navy** (`#0a1f44`) in light mode only.

## Setup

```bash
# 1. Install deps (requires Node 20+)
npm install

# 2. Copy env example & fill in your real secrets
cp .env.example .env
$EDITOR .env

# 3. Run dev server
npm run dev      # http://localhost:4321

# 4. Production build + run
npm run build
npm start        # serves http://localhost:4321 via dist/server/entry.mjs
```

### Environment variables

| Variable | Purpose | Required |
|----------|---------|----------|
| `PUBLIC_TURNSTILE_SITEKEY` | Browser-side Turnstile site key (1xxxxxxxx…) | yes |
| `TURNSTILE_SECRET` | Server-side Turnstile secret key | yes |
| `SMTP_HOST` | SMTP relay host | yes |
| `SMTP_PORT` | `465` (implicit TLS) or `587` (STARTTLS) | yes |
| `SMTP_SECURE` | `true` for port 465 (default), `false` for port 587 | optional |
| `SMTP_USER` / `SMTP_PASS` | SMTP credentials | yes |
| `SMTP_FROM` | e.g. `Prada GL <lalo@pradagl.com>` | yes |
| `SMTP_TO` | Destination address that receives the inquiry | yes |

> The `.env.example` ships with Cloudflare's **public testing keys** (`1x000000…`), so dev builds always pass Turnstile — they don't validate against real users, only the request shape.

## Project layout

```
src/
  styles/
    global.css            # Tailwind import + brand theme tokens
  components/
    Header.astro          # Sticky navy header w/ mobile menu
    Hero.astro            # Bold hero + stats strip
    Services.astro        # 6-card services grid
    About.astro           # Why-us checklist
    Contact.astro         # The form + Turnstile + vanilla JS
    Footer.astro          # Minimal footer
  pages/
    index.astro           # Composes the above
    api/
      contact.ts          # POST endpoint: validation + Turnstile + SMTP
public/
  favicon.svg
```

## How the contact form works

```
┌────────────────────┐   POST /api/contact
│ Contact.astro form │  ────────────────────────────► ┌───────────────────────────┐
└────────────────────┘                                │ pages/api/contact.ts      │
         │                                            │  1. parse + validate      │
         │                                            │  2. verify Turnstile      │
         ▼   cf-turnstile-response token              │  3. send via nodemailer   │
  Cloudflare Turnstile  ── siteverify ──────────────► │                           │
                                                      └───────────────────────────┘
```

The form intercepts `submit`, calls `turnstile.execute()`, and lets Turnstile's `data-callback="onContactTurnstile"` post the form via `fetch('/api/contact')`. TypeScript types are kept end-to-end.

## Deploying

This is a standalone Node app — drop it on any VPS, Render, Railway, Fly.io, etc.

```bash
npm run build
node ./dist/server/entry.mjs
```

For containers:

```Dockerfile
FROM node:20-alpine
WORKDIR /app
COPY package*.json ./
RUN npm ci --omit=dev
COPY dist ./dist
COPY node_modules ./node_modules
ENV HOST=0.0.0.0 PORT=4321
EXPOSE 4321
CMD ["node", "./dist/server/entry.mjs"]
```

Remember to mount the real `.env` (or your platform's secret manager) so `TURNSTILE_SECRET`, `SMTP_*` etc. reach the runtime.

## Testing SMTP locally

If you don't want to spam real inboxes, point `SMTP_HOST`/`SMTP_USER`/`SMTP_PASS` at a [Mailpit](https://github.com/axllent/mailpit) instance:

```bash
docker run --rm -p 1025:1025 -p 8025:8025 axllent/mailpit
```

Then set `SMTP_HOST=127.0.0.1 SMTP_PORT=1025 SMTP_SECURE=false SMTP_USER=anything SMTP_PASS=anything` and watch the messages land at <http://localhost:8025>.

## License

MIT — drop in your own company name, copy, and colors and ship.
