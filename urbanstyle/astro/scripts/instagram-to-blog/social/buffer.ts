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

/** Find the X/Twitter channel (service "twitter") for the Buffer organization. */
export async function getXChannel(): Promise<{ id: string; name: string }> {
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
  const x = channels.find((c) => c.service === "twitter");
  if (!x) throw new Error("No X/Twitter channel found in Buffer");
  return { id: x.id, name: x.name };
}

/**
 * Publish a post immediately (mode: shareNow) to the given channel.
 * If imageUrl is provided, Buffer fetches it server-side and attaches it.
 * Returns the post's external link (the X status URL) when available.
 */
export async function createPost(
  channelId: string,
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
        channelId,
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
