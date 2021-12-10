new Glide(".glide", {
  type: "carousel",
  animationDuration: 1000,
  perView: 1,
  gap: 16,
  peek: 250,
  700: {
    peek: 20,
  },
}).mount();
