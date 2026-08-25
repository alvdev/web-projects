# Design: Google Business Profile publishing (via separate Buffer API)

Date: 2026-08-26
Status: Approved

## Problem

After a post is published to the site, the Telegram bot can publish to X
(via Buffer) and Facebook (direct Graph API, Buffer fallback). We want the
same flow for Google Business Profile (GBP), without touching the existing X
integration.

## Decisions

- **Path:** Buffer — the GBP is already connected as a channel in a SECOND
  Buffer workspace with its own API key (`GBP_BUFFER_ACCESS_TOKEN`).
  Verified live: org `6a8e241d9b6092fade113328`, channel "Urban Style
  Publicity" (`service: googlebusiness`).
- **Isolation:** new standalone module `social/gbp.ts` with its own Buffer
  GraphQL client. `social/buffer.ts` (X path) stays untouched.
- **Text:** reuse the approved Facebook text, stripping `@mentions`
  deterministically — replace `@handle` with the verified page name from
  `fbMentions` when known, otherwise drop the token. No extra approval step.
- **Image:** attach the post header image via `getPostImageUrl(slug)`, like X
  and Facebook.
- **State:** `published.social.gbp` — the `gbp` key already exists in
  `PublishedEntry.social` (types.ts). `SocialPlatformState` fits unchanged.

## Flow

- `publishToGbp()` mirrors `publishToX`/`publishToFb`: gate = approved FB text
  exists → strip mentions → Buffer `createPost` with
  `metadata.google.type: "whats_new"` (verified live against the API —
  `PostTypeGoogleBusiness` enum is `event` | `offer` | `whats_new`) → status
  `published` + alert; errors → status `failed` + error, retry on next tap.
- `social:` callback: GBP is derived from the FB text, so it is "clean"
  whenever an approved/published FB text exists. Publish order X → Facebook →
  GBP. If only GBP is pending it publishes alone; a failed GBP never blocks
  X/Facebook.
- Buffer cannot delete posts (same as X today). GBP local posts expire after
  7 days — Google's rule, not ours.

## Files

- `scripts/instagram-to-blog/social/gbp.ts` (new)
- `scripts/instagram-to-blog/bot.ts` (`publishToGbp` + `social:` callback)
- `.env.example` (+ `GBP_BUFFER_ACCESS_TOKEN`, optional
  `GBP_BUFFER_ORGANIZATION_ID`)

## Verification

No test framework in this repo. Manual: publish a real post via the Telegram
bot and confirm the post appears on the Google Business Profile.

Verified live against the real Buffer API during implementation: channel
lookup, draft post creation with `metadata.google.type: "whats_new"`, and
cleanup via `deletePost`. The bot flow itself still needs one real publish.