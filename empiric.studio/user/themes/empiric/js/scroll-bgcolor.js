const sections = document.querySelectorAll('[data-color]');
const arrow = document.querySelector('#totop svg path');

const sectionObserver = function (entries) {
  entries.forEach(entry => {
    const classes = entry.target.dataset.color.split(' ');
    if (entry.isIntersecting) {
      document.body.classList.add(...classes);
      arrow.style.fill = 'white';
    } else {
      document.body.classList.remove(...classes);
      arrow.style.fill = 'black';
    }
  });
};

const observer = new IntersectionObserver(sectionObserver, {
  threshold: 0.5,
});

sections.forEach(section => observer.observe(section));
