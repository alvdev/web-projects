const colors = require('tailwindcss/colors')

module.exports = {
  //mode: 'jit',
  purge: ['./public/**/*.html'],
  darkMode: 'class', // or 'media' or 'class'
  theme: {
    colors: {
      transparent: 'transparent',
      current: 'currentColor',
      white: colors.white,
      gray: colors.blueGray,
      black: colors.black,
      blue: colors.blue,
      sky: colors.sky,
      teal: colors.teal,
      yellow: colors.amber,
      red: colors.rose,
    },
    extend: {},
  },
  variants: {
    extend: {},
  },
  plugins: [],
}
