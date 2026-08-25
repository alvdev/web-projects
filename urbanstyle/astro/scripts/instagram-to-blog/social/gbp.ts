/**
 * Google Business Profile publishing via Buffer — using a SEPARATE Buffer
 * API key/workspace (GBP_BUFFER_ACCESS_TOKEN). The X integration's
 * BUFFER_ACCESS_TOKEN is intentionally untouched.
 *
 * GBP local posts are plain-text updates (up to 1500 chars, optional photo)
 * shown on Google Search/Maps. Google expires them after 7 days.
 */

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

/** Find the Google Business Profile channel in the GBP Buffer workspace. */
export async function getGbpChannel(): Promise<{ id: string; name: string }> {
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
  const channel = channels.find((c) => c.service === "googlebusiness");
  if (!channel) throw new Error("No Google Business Profile channel found in Buffer");
  return { id: channel.id, name: channel.name };
}

/**
 * Publish a GBP local post immediately (mode: shareNow). Buffer requires
 * metadata.google.type for Google Business Profile channels
 * (PostTypeGoogleBusiness: event | offer | whats_new — "whats_new" is the
 * plain update).
 * If imageUrl is provided, Buffer fetches it server-side and attaches it.
 */
export async function createGbpPost(
  text: string,
  imageUrl?: string,
): Promise<{ id: string; externalLink?: string }> {
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
        channelId: (await getGbpChannel()).id,
        text,
        mode: "shareNow",
        schedulingType: "automatic",
        assets,
        metadata: {
          google: { type: "whats_new" },
        },
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
 * Deterministically remove @mentions: replace each @handle with the verified
 * page name from fbMentions when available, otherwise drop the token.
 */
export function stripMentions(
  text: string,
  fbMentions: { handle: string; pageName: string }[],
): string {
  const byHandle = new Map(fbMentions.map((m) => [m.handle.toLowerCase(), m.pageName]));
  return text
    .replace(/@([\w.]+)/g, (_match, handle: string) => byHandle.get(handle.toLowerCase()) ?? "")
    .replace(/\s{2,}/g, " ")
    .trim();
}