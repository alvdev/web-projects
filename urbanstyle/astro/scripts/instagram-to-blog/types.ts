import { z } from "zod";

export const IgPostSchema = z.object({
  id: z.string(),
  caption: z.string().optional().default(""),
  media_type: z.string(),
  media_url: z.string().nullable().optional(),
  permalink: z.string().optional(),
  thumbnail_url: z.string().nullable().optional(),
  timestamp: z.string(),
  alt_text: z.string().nullable().optional(),
});

export type IgPost = z.infer<typeof IgPostSchema>;

export const IgMediaResponseSchema = z.object({
  data: z.array(IgPostSchema),
  paging: z
    .object({
      next: z.string().nullable().optional(),
    })
    .optional(),
});

export const LlmArticleSchema = z.object({
  title: z.string().min(1),
  description: z.string().min(1),
  content: z.string().min(1),
  tags: z.array(z.string()).min(1),
  category: z.string().min(1),
});

export type LlmArticle = z.infer<typeof LlmArticleSchema>;

export interface PreparedPost {
  title: string;
  description: string;
  content: string;
  tags: string[];
  category: string;
  slug: string;
  pubDate: string;
  basePath: string;
  media_url: string;
  igMediaId: string;
  mdxPath: string;
  imagePath: string;
  objectPosition?: "top" | "center" | "bottom";
}

export interface NewPost {
  id: string;
  caption: string;
  mediaUrl: string;
  timestamp: string;
  mediaType: string;
}

export type PendingStatus = "pending" | "regenerating";

export type LlmProvider = "gemini" | "deepseek";

export type SocialStatus = "queued" | "approved" | "published" | "failed";

export interface VerifiedHandle {
  handle: string;
  status: "verified" | "invalid" | "unverified";
  exists?: boolean;
  followers?: number;
  isVerified?: boolean;
  displayName?: string;
  error?: string;
  verifiedAt: string;
}

export interface SocialPlatformState {
  status: SocialStatus;
  tweet?: string;        // approved tweet text (FB text for facebook)
  tweetProvider?: LlmProvider;  // provider chosen for the tweet
  publishedAt?: string;
  error?: string;
  handlesApproved?: boolean; // user approved the FB text WITH @mentions as-is
  fbMentions?: { handle: string; pageName: string; pageUrl?: string }[]; // verified pages per @handle
  verifiedHandles?: VerifiedHandle[]; // X verification cache (with verifiedAt)
}

export interface PublishedEntry {
  id: string;
  slug: string;
  title: string;
  publishedAt: string;
  caption?: string;
  postTimestamp?: string;
  mediaType?: string;
  social: Partial<Record<"x" | "facebook" | "gbp" | "linkedin", SocialPlatformState>>;
}

export interface PendingEntry {
  id: string;
  article: LlmArticle;
  post: NewPost;
  prepared: PreparedPost;
  status: PendingStatus;
  feedback: string | null;
  createdAt: string;
  attempts: number;
  articles?: { gemini?: LlmArticle; deepseek?: LlmArticle };
  chosenProvider?: LlmProvider;
  chosenTitleProvider?: LlmProvider;
  chosenContentProvider?: LlmProvider;
}

export interface PendingState {
  lastProcessedId?: string;
  highestSeenId?: string;
  tokenExpiresAt?: number;
  chatId?: number;
  backlogDone?: boolean;
  skippedIds: string[];
  pending: PendingEntry[];
  published: PublishedEntry[];
  knownXHandles?: Record<string, string>; // artista (normalizado) → @handle X verificado
  knownFbPages?: Record<string, { user: string; url: string }>; // @handle → página FB verificada
}

export interface Instruction {
  id: string;
  text: string;
  source: "feedback" | "manual";
  createdAt: string;
  timesApplied: number;
}
