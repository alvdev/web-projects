import { defineCollection, z } from "astro:content";
import { glob } from "astro/loaders";

const logos = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/sections/logos" }),
  schema: ({ image }) =>
    z.object({
      logos: z.array(
        z.object({
          src: image(),
          alt: z.string(),
        }),
      ),
    }),
});

const reviews = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/sections/reviews" }),
  schema: ({ image }) => 
    z.object({
      title: z.string(),
      subtitle: z.string(),
      enabledTitles: z.boolean(),
      reviews: z.array(
        z.object({
          author: z.string(),
          picture: image(),
          link: z.string().url(),
          text: z.string(),
          stars: z.number().min(1).max(5),
        }),
      ),
    })
});

const faqs = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/sections/faqs" }),
  schema: () =>
    z.object({
      title: z.string(),
      columns: z.number(),
      description: z.string(),
      faqs: z.array(
        z.object({
          question: z.string(),
          answer: z.string(),
        }),
      ),
    }),
});

const carousel = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/sections/carousel" }),
  schema: ({ image }) =>
    z.object({
      title: z.string(),
      subtitle: z.string(),
      description: z.string(),
      enabledTitles: z.boolean(),
      images: z.array(
        z.object({
          src: image(),
          alt: z.string(),
          desc: z.string(),
        }),
      ),
    }),
});

const benefits = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/sections/benefits" }),
  schema: ({ image}) =>
    z.object({
      title: z.string(),
      subtitle: z.string(),
      enabledTitles: z.boolean(),
      benefits: z.array(
        z.object({
          title: z.string(),
          description: z.string(),
          image: image(),
          alt: z.string(),
        }),
      ),
    }),
});

const tabs = defineCollection({
  loader: glob({ pattern: "**/*.md", base: "./src/content/sections/tabs" }),
  schema: () =>
    z.object({
      title: z.string(),
      subtitle: z.string(),
      order: z.number(),
    })
})

export const collections = { logos, reviews, faqs, carousel, benefits, tabs };
