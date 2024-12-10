/** @type {import('tailwindcss').Config} */
const defaultTheme = require('tailwindcss/defaultTheme');

export default {
  content: ['./site/**/*.{html,js,php}"'],
  theme: {
    container: {
      padding: '5vw',
    },
    extend: {
      colors: {
        green: {
          '100': 'hsl(148, 100%, 90%)',
          '200': 'hsl(148, 100%, 80%)',
          '300': 'hsl(148, 100%, 70%)',
          '400': 'hsl(148, 100%, 60%)',
          '500': 'hsl(148, 100%, 50%)',
          '600': 'hsl(148, 100%, 40%)',
          '700': 'hsl(148, 100%, 30%)',
          '800': 'hsl(148, 100%, 20%)',
          '900': 'hsl(148, 100%, 10%)',
        }
      },
      fontFamily: {
        mono: ['mono', ...defaultTheme.fontFamily.mono],
      },
      fontSize: {
        '10xl': ['10rem', { lineHeight: '0.8', letterSpacing: '-0.05em' }],
      },
      borderWidth: {
        3: '3px',
        5: '5px',
        6: '6px',
      },
      keyframes: {
        wiggle: {
          '0%, 100%': { transform: 'rotate(-3deg)' },
          '50%': { transform: 'rotate(3deg)' },
        },
      },
      animation: {
        wiggle: 'wiggle 1s ease-in-out infinite',
      },
    },
  },
  plugins: [
    /* require('tailwindcss-debug-screens'), */
    require('@tailwindcss/typography'),
  ],
};
