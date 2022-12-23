new Glide('.glide-email', {
  type: 'carousel',
  animationDuration: 1000,
  perView: 1,
  gap: 16,
  peek: 250,
  breakpoints: {
    800: {
      peek: 150,
    },
    400: {
      peek: 50,
    },
  },
}).mount();
