new Glide('.glide-header', {
  type: 'carousel',
  animationDuration: 1000,
}).mount();

// import { siblings } from '@glidejs/glide/src/utils/dom';

// const classes = {
//   glideSlideNextActive: 'glide__slide--next-active',
// };

// const selectors = {
//   glideSlideNextActive: '.glide__slide--next-active',
//   glideTrack: '.glide__track',
// };

// function ResizeSlider(Glide, Components, Events) {
//   var Component = {
//     mount() {
//       this.changeActiveSlide();
//       this.updateTrackHeight();
//     },

//     changeActiveSlide() {
//       let slide = Components.Html.slides[Glide.index];
//       slide.classList.add(classes.glideSlideNextActive);

//       siblings(slide).forEach(sibling => {
//         sibling.classList.remove(classes.glideSlideNextActive);
//       });
//     },

//     updateTrackHeight() {
//       console.log('update track height');

//       const activeSlide = document.querySelector(
//         selectors.glideSlideNextActive
//       );
//       const activeSlideHeight = activeSlide ? activeSlide.offsetHeight : 0;

//       const glideTrack = document.querySelector(selectors.glideTrack);
//       const glideTrackHeight = glideTrack ? glideTrack.offsetHeight : 0;

//       console.log(
//         `Active slide: ${activeSlide} activeSlideHeight: ${activeSlideHeight}`
//       );

//       if (activeSlideHeight !== glideTrackHeight) {
//         glideTrack.style.height = `${activeSlideHeight}px`;
//       }
//     },
//   };

//   Events.on('run', () => {
//     Component.changeActiveSlide();
//     Component.updateTrackHeight();
//   });

//   return Component;
// }

// const slider = new Glide(this.container, {
//   type: 'carousel',
//   animationDuration: 350,
// });

// slider.mount({
//   ResizeSlider: ResizeSlider,
// });
