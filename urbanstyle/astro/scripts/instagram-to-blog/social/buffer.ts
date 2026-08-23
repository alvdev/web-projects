/**
 * Buffer API client (GraphQL at https://api.buffer.com/graphql).
 * Posts to the connected X/Twitter channel with optional image (by URL —
 * Buffer downloads it server-side).
 */

const API_URL = "https://api.buffer.com/graphql";

function token(): string {
  const t = process.env.BUFFER_ACCESS_TOKEN ?? "";
  if (!t) throw new Error("BUFFER_ACCESS_TOKEN not set");
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

/** Find a channel by Buffer service name for the Buffer organization. */
async function getChannel(service: "twitter" | "facebook"): Promise<{ id: string; name: string }> {
  const orgId =
    process.env.BUFFER_ORGANIZATION_ID ??
    (async () => {
      const org = await gql<OrgQuery>("{ account { organizations { id } } }");
      return org.account.organizations[0]?.id;
    })();

  const { channels } = await gql<ChannelsQuery>(
    "query Channels($orgId: OrganizationId!) { channels(input: { organizationId: $orgId }) { id name service } }",
    { orgId: await orgId },
  );
  const channel = channels.find((c) => c.service === service);
  if (!channel) throw new Error(`No ${service} channel found in Buffer`);
  return { id: channel.id, name: channel.name };
}

/** Find the X/Twitter channel. */
export async function getXChannel(): Promise<{ id: string; name: string }> {
  return getChannel("twitter");
}

/** Find the Facebook page channel. */
export async function getFbChannel(): Promise<{ id: string; name: string }> {
  return getChannel("facebook");
}

/**
 * Publish a post immediately (mode: shareNow) to the given channel.
 * If imageUrl is provided, Buffer fetches it server-side and attaches it.
 * facebookType is REQUIRED by Buffer for Facebook channels (metadata.facebook.type
 * is NON_NULL in the schema: post | story | reel); other services ignore it.
 * facebookAnnotations lets Buffer tag spans of the text (mentions/link
 * annotations): { content, indices: [start, end], text, url }.
 * Returns the post's external link (the X status URL) when available.
 */
export async function createPost(
  channelId: string,
  text: string,
  imageUrl?: string,
  facebookType?: "post" | "story" | "reel",
  facebookAnnotations?: { content: string; indices: number[]; text: string; url: string }[],
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
        channelId,
        text,
        mode: "shareNow",
        schedulingType: "automatic",
        assets,
        ...(facebookType || facebookAnnotations
          ? {
              metadata: {
                facebook: {
                  ...(facebookType ? { type: facebookType } : {}),
                  ...(facebookAnnotations ? { annotations: facebookAnnotations } : {}),
                },
              },
            }
          : {}),
      },
    },
  );

  const payload = result.createPost;
  if ("post" in payload) {
    return { id: payload.post.id, externalLink: payload.post.externalLink };
  }
  throw new Error(`Buffer createPost failed: ${payload.message ?? "unknown error"}`);
}
