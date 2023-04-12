const totop = document.querySelector('#totop');

window.addEventListener('scroll', () => {
  totop.hidden = window.scrollY > window.innerHeight ? false : true;
});

totop.addEventListener('click', () => {
  document.documentElement.scrollTop = 0;
});
