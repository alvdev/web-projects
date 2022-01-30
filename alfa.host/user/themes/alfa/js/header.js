new Glide('.glide-header', {
  type: 'carousel',
  animationDuration: 1000,
  perView: 1,
  gap: 0,
  peek: 0,
  breakpoints: {
    800: {
      peek: 0,
    },
    400: {
      peek: 0,
    },
  },
}).mount;
