const totop = document.querySelector('#totop');

window.addEventListener('scroll', () => {
  totop.hidden = window.scrollY > 500 ? false : true;
});

totop.addEventListener('click', () => {
  document.documentElement.scrollTop = 0;
});
