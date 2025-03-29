// @ts-check
import { defineConfig } from 'astro/config';
import { fileURLToPath } from 'url';

import alpinejs from '@astrojs/alpinejs';
import tailwindcss from '@tailwindcss/vite';

// https://astro.build/config
export default defineConfig({
  site: "https://urbanstyle.com/test",
  base: "/test",
  trailingSlash: 'never',
  
  integrations: [alpinejs({entrypoint: '/src/alpinejs'})],

  vite: {
    plugins: [tailwindcss()],
    // process images from css workaround
    resolve: {
      alias: {
        '@assets': fileURLToPath(new URL('./src/assets', import.meta.url))
      }
    }
  },

  image: {
    experimentalLayout: 'responsive',
  },

  experimental: {
    responsiveImages: true,
    contentIntellisense: true,
    svg: true
  }
});
