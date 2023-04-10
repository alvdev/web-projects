const sections = document.querySelectorAll('[data-color]');
const arrow = document.querySelector('#totop svg path');

window.addEventListener('scroll', () => {
  for (let section of sections) {
    const classes = section.dataset.color.split(' ');
    if (
      window.scrollY >= section.offsetTop - window.innerHeight / 3 &&
      window.scrollY <=
        section.offsetTop + section.clientHeight - window.innerHeight / 3
    ) {
      document.body.classList.add(...classes);
      arrow.style.fill = 'white';
      break;
    } else {
      document.body.classList.remove(...classes);
      arrow.style.fill = 'black';
    }
  }
});
