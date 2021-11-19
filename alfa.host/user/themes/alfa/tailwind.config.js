const colors = require('tailwindcss/colors');

module.exports = {
  mode: "jit",
  purge: [
    '../../config/**/*.yaml',
    '../../pages/**/*.md',
    './blueprints/**/*.yaml',
    './js/**/*.js',
    './templates/**/*.twig',
    './alfa.yaml',
    './alfa.php'
  ],
  darkMode: 'false', //false or 'media' or 'class'
  theme: {
    extend: {
      screens: {
        sm: '640px',
        md: '768px',
        lg: '1024px',
        xl: '1280px',
        '2xl': '1536px'
      },
      height: {
        pxx: '2px'
      },
    },
    colors: {
      white: colors.white,
      gray: colors.blueGray,
      black: colors.black,
      blue: colors.blue,
      sky: colors.sky,
      teal: colors.teal,
      yellow: colors.amber,
      red: colors.rose,
      current: 'currentColor',
      transparent: 'transparent',
      'inherit': 'inherit',
    },
    typography: (theme) => ({
      DEFAULT: {
        css: {
          color: 'inherit',
          lineHeight: 'inherit',
          maxWidth: 'inherit',
          a: {
            transition: 'all 500ms',
            color: theme('colors.primary.DEFAULT'),
            '&:hover': {
              color: theme('colors.primary.darker')
            },
            textDecoration: 'none'
          },
          strong: {
            color: 'inherit'
          },
        }
      }
    }),
  },
  variants: {
    extend: {},
  },
  plugins: [
    require('@tailwindcss/typography'),
    require('@tailwindcss/forms'),
    require('tailwindcss-debug-screens'),
  ],
  important: false,
}
