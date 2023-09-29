let mix = require('laravel-mix');
require('mix-tailwindcss');

const extension = mix.inProduction() ? '.min' : '';
const partytown = require('./node_modules/@builder.io/partytown/utils');

mix
  .js('js/main.js', `js/main${extension}.js`)
  .css('css/site.css', `css/site${extension}.css`)
  .tailwind()
  .setPublicPath('dist')
  .version()
  .copy('icons', 'dist/icons')
  .copy('images', 'dist/images')
  .copy(partytown.libDirPath(), 'dist/~partytown')
  .options({
    processCssUrls: false,
  });
// .browserSync({
//     proxy: 'http://urban.local',
//     files: [
//         './dist/mix-manifest.json',
//         '../../pages/**/*.md'
//     ]
// })
