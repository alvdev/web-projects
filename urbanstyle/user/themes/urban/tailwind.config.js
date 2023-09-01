/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    '../../config/**/*.yaml',
    '../../pages/**/*.md',
    './blueprints/**/*.yaml',
    './js/**/*.js',
    './templates/**/*.twig',
    './urban.yaml',
    './urban.php',
  ],
  darkMode: 'class', //false or 'media' or 'class'
  theme: {},
  variants: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
    require('tailwindcss-debug-screens'),
  ],
  theme: {
    extend: {
      ringWidth: {
        16: '16px',
      },
      saturate: {
        110: '1.1',
        125: '1.25',
      },
      contrast: {
        110: '1.1',
      },
      scale: {
        102: '1.02',
      },
    },
    container: {
      padding: '4%',
      center: true,
    },
  },
};
