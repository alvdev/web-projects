const sections = document.querySelectorAll('[data-color]');
const arrow = document.querySelector('#totop svg path');
const empiric = document.querySelectorAll('aside .logo path.c');
const triangle = document.querySelector('aside .logo polygon.a');
const aside = document.querySelector('aside');

const sectionObserver = function (entries) {
  entries.forEach(entry => {
    const classes = entry.target.dataset.color.split(' ');
    if (entry.isIntersecting) {
      document.body.classList.add(...classes);
      triangle.style.fill = '#7e22ce';
      empiric.forEach(c => (c.style.fill = 'white'));
      arrow.style.fill = 'white';
      aside.classList.add(
        'bg-gray-900',
        'shadow-[0_0_30px_30px_rgb(24,24,27)]'
      );
      aside.classList.remove(
        'bg-white',
        'shadow-[0_0_30px_30px_rgb(255,255,255)]'
      );
    } else {
      document.body.classList.remove(...classes);
      triangle.style.fill = 'gold';
      empiric.forEach(c => (c.style.fill = '#101010'));
      arrow.style.fill = 'black';
      aside.classList.add(
        'bg-white',
        'shadow-[0_0_30px_30px_rgb(255,255,255)]'
      );
      aside.classList.remove(
        'bg-gray-900',
        'shadow-[0_0_30px_30px_rgb(24,24,27)]'
      );
    }
  });
};

const observer = new IntersectionObserver(sectionObserver, {
  threshold: 0.5,
});

sections.forEach(section => observer.observe(section));
