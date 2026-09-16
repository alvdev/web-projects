import type { LlmArticle, NewPost } from "../types";

/** Fixture IG post used by the sandbox fake pipeline. */
export const sandboxPost: NewPost = {
  id: "9000000000000001",
  caption:
    "🔥 Últimos carteles pegados en Madrid para el concierto de verano. ¡No te lo pierdas! #streetmarketing #carteles",
  mediaUrl: "",
  timestamp: new Date(Date.now() - 2 * 24 * 60 * 60 * 1000).toISOString(),
  mediaType: "IMAGE",
};

/** Canned article (the sandbox never calls the LLMs). */
export const sandboxArticle: LlmArticle = {
  title: "Concierto de verano en Madrid: carteles por toda la ciudad",
  description: "La campaña de carteles anuncia el concierto de verano en Madrid.",
  content: [
    "## Una campaña por toda la ciudad",
    "",
    "Los carteles ya se pueden ver en las calles de Madrid anunciando el concierto de verano.",
    "",
    "- Carteles pegados en el centro",
    "- Campaña activa durante el mes",
    "",
    "No te pierdas el directo.",
  ].join("\n"),
  tags: ["conciertos", "madrid", "street marketing"],
  category: "Conciertos",
};

/** Approved X text used by the sandbox social phase. */
export const sandboxTweet =
  "Los carteles ya están pegados en Madrid anunciando el concierto de verano https://urbanstylepublicity.com/blog/sandbox-post";

/** Approved Facebook/GBP/LinkedIn text (mentions stripped for GBP/LinkedIn). */
export const sandboxFbText = "Los carteles ya están pegados en Madrid anunciando el concierto de verano.";
