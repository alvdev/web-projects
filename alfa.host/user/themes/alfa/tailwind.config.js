const colors = require('tailwindcss/colors');

module.exports = {
  content: [
    '../../config/**/*.yaml',
    '../../pages/**/*.md',
    './blueprints/**/*.yaml',
    './js/**/*.js',
    './templates/**/*.twig',
    './templates/**/*.svg',
    './alfa.yaml',
    './alfa.php',
  ],
  theme: {
    extend: {
      screens: {
        sm: '640px',
        md: '768px',
        lg: '1024px',
        xl: '1280px',
        '2xl': '1536px',
      },
      height: {
        pxx: '2px',
      },
    },
    dropShadow: {
      icon: '4px -4px 4px rgba(15, 23, 42, 0.20)',
      xs: '0 2px 2px rgba(15, 23, 42, 0.20)',
      lg: '0 6px 6px rgba(15, 23, 42, 0.20)',
      xl: '0 10px 10px rgba(15, 23, 42, 0.20)',
      '2xl': '0 20px 20px rgba(15, 23, 42, 0.20)',
      '3xl': '0 35px 35px rgba(15, 23, 42, 0.20)',
      '4xl': [
        '0 35px 35px rgba(15, 23, 42, 0.25)',
        '0 45px 65px rgba(15, 23, 42, 0.15)',
      ],
    },
    colors: {
      white: colors.white,
      gray: colors.slate,
      black: colors.black,
      blue: colors.blue,
      sky: colors.sky,
      cyan: colors.cyan,
      teal: colors.teal,
      yellow: colors.amber,
      red: colors.rose,
      current: 'currentColor',
      transparent: 'transparent',
    },
    typography: theme => ({
      DEFAULT: {
        css: {
          color: 'inherit',
          lineHeight: 'inherit',
          maxWidth: 'inherit',
          a: {
            transition: 'all 500ms',
            color: theme('colors.primary.DEFAULT'),
            '&:hover': {
              color: theme('colors.primary.darker'),
            },
            textDecoration: 'none',
          },
          strong: {
            color: 'inherit',
          },
        },
      },
    }),
  },
  plugins: [
    require('@tailwindcss/typography'),
    require('@tailwindcss/forms'),
    require('tailwindcss-debug-screens'),
    require('tailwind-scrollbar')({ nocompatible: true }),
  ],
  important: false,
};
