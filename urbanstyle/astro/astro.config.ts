// @ts-check
import { defineConfig } from "astro/config";
import { fileURLToPath } from "url";

import alpinejs from "@astrojs/alpinejs";
import tailwindcss from "@tailwindcss/vite";

import mdx from "@astrojs/mdx";

// https://astro.build/config
export default defineConfig({
  site: "https://urbanstylepublicity.com",
  base: "/test/",
  // trailingSlash: 'never',

  integrations: [alpinejs({ entrypoint: "/src/alpinejs" }), mdx()],

  vite: {
    plugins: [tailwindcss() as any],
    // process images from css workaround
    resolve: {
      alias: {
        "@assets": fileURLToPath(new URL("./src/assets", import.meta.url)),
      },
    },
    server: {
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
    experimentalLayout: "responsive",
  },

  experimental: {
    responsiveImages: true,
    contentIntellisense: true,
    svg: true,
  },
});
