// @ts-check
import { defineConfig } from "astro/config";
import { fileURLToPath } from "url";

import alpinejs from "@astrojs/alpinejs";
import tailwindcss from "@tailwindcss/vite";

import mdx from "@astrojs/mdx";

import sitemap from "@astrojs/sitemap";

const isProd = import.meta.env.PROD;
// https://astro.build/config
export default defineConfig({
  site: isProd ? "https://www.urbanstylepublicity.com" : "http://urban.local:4321",
  base: isProd ? "/" : "/",
  // trailingSlash: 'never',

  integrations: [alpinejs({ entrypoint: "/src/alpinejs" }), mdx(), sitemap()],

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
    locales: ["es", "en"],
    defaultLocale: "es",
    routing: {
      prefixDefaultLocale: false,
    }
  },

  image: {
    experimentalLayout: "constrained",
  },

  experimental: {
    responsiveImages: true,
    contentIntellisense: true,
  },
});
