/**
 * LinkedIn publishing via Buffer — same SEPARATE Buffer workspace/key as GBP
 * (GBP_BUFFER_ACCESS_TOKEN). Channel: "urban-style-publicity" (service:
 * "linkedin", id 6a90c4c6ccaf649a672e0050, verified 2026-08-28).
 *
 * LinkedIn needs no metadata (unlike Facebook/Google): a plain text + optional
 * image post works. @mentions are stripped by the caller (stripMentions from
 * gbp.ts) — real LinkedIn mentions would require resolving org URNs
 * (AnnotationInputLinkedIn), left as future work.
 */

import { DRY_RUN_LINK, dryRunLog, isDryRun } from "../dryRun";

const API_URL = "https://api.buffer.com/graphql";

function token(): string {
  const t = process.env.GBP_BUFFER_ACCESS_TOKEN ?? "";
  if (!t) throw new Error("GBP_BUFFER_ACCESS_TOKEN not set");
  return t;
}

async function gql<T>(query: string, variables?: Record<string, unknown>): Promise<T> {
  const res = await fetch(API_URL, {
    method: "POST",
    headers: {
      Authorization: `Bearer ${token()}`,
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ query, variables }),
  });
  if (!res.ok) throw new Error(`Buffer API HTTP ${res.status}`);
  const data = (await res.json()) as { errors?: { message: string }[]; data?: T };
  if (data.errors?.length) {
    throw new Error(`Buffer API: ${data.errors.map((e) => e.message).join("; ")}`);
  }
  return data.data as T;
}

interface OrgQuery {
  account: { organizations: { id: string }[] };
}

interface ChannelsQuery {
  channels: { id: string; name: string; service: string }[];
}

interface CreatePostResult {
  createPost:
    | { post: { id: string; externalLink?: string } }
    | { message?: string };
}

interface PostQuery {
  post: {
    id: string;
    status: string;
    externalLink?: string | null;
    error?: { message: string } | null;
  } | null;
}

export interface LinkedInPost {
  id: string;
  status: string;
  externalLink?: string;
  error?: string;
}

const LINK_POLL_INTERVAL_MS = 3_000;
const LINK_POLL_TIMEOUT_MS = 60_000;

/** Find the LinkedIn channel in the GBP Buffer workspace. */
export async function getLinkedInChannel(): Promise<{ id: string; name: string }> {
  if (isDryRun()) {
    dryRunLog("LinkedIn channel lookup skipped");
    return { id: "lich", name: "dry-run linkedin" };
  }
  const orgId =
    process.env.GBP_BUFFER_ORGANIZATION_ID ??
    (async () => {
      const org = await gql<OrgQuery>("{ account { organizations { id } } }");
      return org.account.organizations[0]?.id;
    })();

  const { channels } = await gql<ChannelsQuery>(
    "query Channels($orgId: OrganizationId!) { channels(input: { organizationId: $orgId }) { id name service } }",
    { orgId: await orgId },
  );
  const channel = channels.find((c) => c.service === "linkedin");
  if (!channel) throw new Error("No LinkedIn channel found in Buffer");
  return { id: channel.id, name: channel.name };
}

/**
 * Publish a LinkedIn post immediately (mode: shareNow) to the page channel.
 * If imageUrl is provided, Buffer fetches it server-side and attaches it.
 * LinkedIn requires no metadata.
 */
export async function createLinkedInPost(
  text: string,
  imageUrl?: string,
): Promise<{ id: string; externalLink?: string }> {
  if (isDryRun()) {
    dryRunLog("LinkedIn createLinkedInPost skipped");
    return { id: "dry-linkedin", externalLink: `${DRY_RUN_LINK}/linkedin` };
  }
  const assets = imageUrl ? [{ image: { url: imageUrl } }] : [];
  const result = await gql<CreatePostResult>(
    `mutation CreatePost($input: CreatePostInput!) {
      createPost(input: $input) {
        ... on PostActionSuccess { post { id externalLink } }
        ... on InvalidInputError { message }
        ... on UnauthorizedError { message }
        ... on LimitReachedError { message }
        ... on UnexpectedError { message }
      }
    }`,
    {
      input: {
        channelId: (await getLinkedInChannel()).id,
        text,
        mode: "shareNow",
        schedulingType: "automatic",
        assets,
      },
    },
  );

  const payload = result.createPost;
  if ("post" in payload) {
    return { id: payload.post.id, externalLink: payload.post.externalLink };
  }
  throw new Error(`Buffer createPost failed: ${payload.message ?? "unknown error"}`);
}

/**
 * Fetch a Buffer post by id. Buffer publishes LinkedIn asynchronously: right
 * after createPost the post still has no externalLink and the URL shows up a
 * few seconds later, so callers poll this.
 */
export async function getLinkedInPost(id: string): Promise<LinkedInPost> {
  if (isDryRun()) {
    dryRunLog("LinkedIn getLinkedInPost skipped");
    return { id, status: "sent", externalLink: `${DRY_RUN_LINK}/linkedin` };
  }
  const result = await gql<PostQuery>(
    `query Post($input: PostInput!) {
      post(input: $input) { id status externalLink error { message } }
    }`,
    { input: { id } },
  );
  if (!result.post) throw new Error(`Buffer post ${id} not found`);
  return {
    id: result.post.id,
    status: result.post.status,
    externalLink: result.post.externalLink ?? undefined,
    error: result.post.error?.message,
  };
}

/**
 * Wait (bounded) until Buffer exposes the published LinkedIn post URL.
 * Returns undefined on timeout — the post DID publish, only the link is
 * missing. Throws when Buffer reports a publishing error.
 */
export async function waitForLinkedInExternalLink(
  id: string,
  options: { timeoutMs?: number; intervalMs?: number } = {},
): Promise<string | undefined> {
  const timeoutMs = options.timeoutMs ?? LINK_POLL_TIMEOUT_MS;
  const intervalMs = options.intervalMs ?? LINK_POLL_INTERVAL_MS;
  const deadline = Date.now() + timeoutMs;

  for (;;) {
    const post = await getLinkedInPost(id);
    if (post.externalLink) return post.externalLink;
    if (post.status === "error") {
      throw new Error(`Buffer LinkedIn publish failed: ${post.error ?? "unknown error"}`);
    }
    if (Date.now() >= deadline) return undefined;
    await new Promise((resolve) => setTimeout(resolve, Math.min(intervalMs, deadline - Date.now())));
  }
}
