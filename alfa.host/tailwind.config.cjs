const colors = require('tailwindcss/colors')

const config = {
  mode: "jit",
  purge: [
    "./src/**/*.{html,js,svelte,ts}",
  ],
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
  plugins: [],
};

module.exports = config;
