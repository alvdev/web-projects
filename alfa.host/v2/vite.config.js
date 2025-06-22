// vite.config.js
import kirby from 'vite-plugin-kirby';
import tailwindcss from '@tailwindcss/vite';

export default ({ mode }) => ({
  // During development the assets are served directly from vite's dev server
  // e.g. `localhost:5173/index.js`, but for production they are placed inside
  // the `build.outDir`, `/dist/` in this case.
  base: mode === 'development' ? '/' : '/dist/',

  build: {
    // Where your manifest an bundled assets will be placed. This example
    // assumes you use a public folder structure.
    outDir: 'dist',
    assetsDir: 'assets',

    // Your entry file(s).
    // Note: CSS files can either be a separate entry. In this case you use it
    // like this: `<?= vite->css('main.css') ?>`. Or you can only add the
    // `main.js` as an entry and import the CSS in your JS file. In this case
    // you would use the JS file name: `vite()->css('main.js')`.
    rollupOptions: {
      input: [
        'assets/js/main.js',
        'assets/css/main.css',
        ...(mode === 'development' ? ['assets/css/debug-screens.css'] : []),
      ],
      output: {
        assetFileNames: assetInfo => {
          if (/\.css$/i.test(assetInfo.name))
            return 'assets/css/[name]-[hash][extname]';
          if (/\.(png|jpe?g|gif|svg|webp|ico)$/i.test(assetInfo.name))
            return 'assets/images/[name]-[hash][extname]';
          if (/\.(woff2?|eot|ttf|otf)$/i.test(assetInfo.name))
            return 'assets/fonts/[name]-[hash][extname]';
          return 'assets/[name]-[hash][extname]';
        },
        chunkFileNames: 'assets/js/[name]-[hash].js',
        entryFileNames: 'assets/js/[name]-[hash].js',
      },
    },
  },

  plugins: [
    tailwindcss(),
    kirby({
      // By default Kirby's templates, snippets, controllers, models, layouts and
      // everything inside the content folder will be watched and a full reload
      // triggered. All paths are relative to Vite's root folder.
      watch: [
        './site/(templates|snippets|controllers|models|layouts)/**/*.php',
        './content/**/*',
      ],
      // or disable watching
      // watch: false,

      // Where the automatically generated `vite.config.php` file should be
      // placed. This has to match Kirby's config folder!
      kirbyConfigDir: 'site/config', // default
    }),
  ],
});
