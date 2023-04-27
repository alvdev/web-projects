const sections = document.querySelectorAll('[data-color]');
const arrow = document.querySelector('#totop svg path');
const empiric = document.querySelectorAll('aside .logo path.c');
const triangle = document.querySelector('aside .logo polygon.a');

const sectionObserver = function (entries) {
  entries.forEach(entry => {
    const classes = entry.target.dataset.color.split(' ');
    if (entry.isIntersecting) {
      document.body.classList.add(...classes);
      triangle.style.fill = '#7e22ce';
      empiric.forEach(c => (c.style.fill = 'white'));
      arrow.style.fill = 'white';
    } else {
      document.body.classList.remove(...classes);
      triangle.style.fill = 'gold';
      empiric.forEach(c => (c.style.fill = '#101010'));
      arrow.style.fill = 'black';
    }
  });
};

const observer = new IntersectionObserver(sectionObserver, {
  threshold: 0.5,
});

sections.forEach(section => observer.observe(section));
