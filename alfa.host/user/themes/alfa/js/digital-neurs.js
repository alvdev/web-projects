new Glide(".glide-neurs", {
  type: "carousel",
  animationDuration: 1000,
  perView: 2,
  autoplay: 4000,
  breakpoints: {
    700: {
      perView: 1,
    },
  },
}).mount();
