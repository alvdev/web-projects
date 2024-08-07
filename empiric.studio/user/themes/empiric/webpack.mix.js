let mix = require('laravel-mix');
require('mix-tailwindcss');

const extension = mix.inProduction() ? '.min' : '';

mix
  .js('js/main.js', `js/main${extension}.js`)
  .css('css/site.css', `css/site${extension}.css`)
  .tailwind()
  .setPublicPath('dist')

  .version();
//mix.copy("./fonts", "./dist/fonts");
mix
  .setResourceRoot('../../')

  .browserSync({
    proxy: 'http://empiric.local',
    host: 'empiric.local',
    files: ['./dist/mix-manifest.json', '../../pages/**/*.md'],
  });
