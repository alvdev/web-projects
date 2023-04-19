const purgecss = require('@fullhuman/postcss-purgecss');

module.exports = {
  plugins: [
    purgecss({
      content: ['js/**/*.js', 'templates/**/*.twig'],
    }),
  ],
};
