module.exports = {
  plugins: {
    'postcss-import': {},
    'tailwindcss': {},
    'postcss-nested': {},
    'autoprefixer': {},
    ...(process.env.NODE_ENV === 'production' ? { cssnano: {} } : {}),
  },
};
