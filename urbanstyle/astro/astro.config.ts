// @ts-check
import { defineConfig } from "astro/config";
import { fileURLToPath } from "url";

import alpinejs from "@astrojs/alpinejs";
import tailwindcss from "@tailwindcss/vite";

// https://astro.build/config
export default defineConfig({
  site: "https://urbanstylepublicity.com",
  base: "/test/",
  // trailingSlash: 'never',

  integrations: [alpinejs({ entrypoint: "/src/alpinejs" })],

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

  image: {
    experimentalLayout: "responsive",
  },

  experimental: {
    responsiveImages: true,
    contentIntellisense: true,
    svg: true,
  },
});
