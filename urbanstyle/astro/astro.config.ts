// @ts-check
import { defineConfig } from "astro/config";
import { fileURLToPath } from "url";

import alpinejs from "@astrojs/alpinejs";
import tailwindcss from "@tailwindcss/vite";

import mdx from "@astrojs/mdx";

import sitemap from "@astrojs/sitemap";

import partytown from "@astrojs/partytown";

const isProd = import.meta.env.PROD;
// https://astro.build/config
export default defineConfig({
  site: isProd ? "https://urbanstylepublicity.com" : "http://urban.local:4321",
  base: isProd ? "/" : "/",
  // trailingSlash: 'never',

  integrations: [
    partytown({
      config: {
        forward: ["dataLayer.push"],
      },
    }),
    alpinejs({ entrypoint: "/src/alpinejs" }),
    mdx(),
    sitemap({
      i18n: {
        defaultLocale: "es",
        locales: {
          es: "es",
          en: "en",
          it: "it",
          fr: "fr",
          pt: "pt",
        },
      },
    }),
  ],

  vite: {
    plugins: [tailwindcss() as any],
    // process images from css workaround
    resolve: {
      alias: {
        "@assets": fileURLToPath(new URL("./src/assets", import.meta.url)),
      },
    },
    server: {
      allowedHosts: ["urban.local"],
      proxy: {
        "/php": {
          target: "http://localhost:8000",
          changeOrigin: true,
          rewrite: path => path.replace(/^\/php/, ""),
        },
      },
    },
  },

  i18n: {
    locales: ["es", "en", "it", "fr", "pt"],
    defaultLocale: "es",
    routing: {
      prefixDefaultLocale: false,
    },
  },

  image: {
    // experimentalLayout: "constrained",
  },

  experimental: {
    // responsiveImages: true,
    contentIntellisense: true,
  },
});
