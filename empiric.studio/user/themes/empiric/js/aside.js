const invisibles = document.querySelectorAll('.invisible');

document.addEventListener('scroll', () => {
  window.scrollY >= window.innerHeight / 1.5
    ? invisibles.forEach(i => i.classList.remove('invisible'))
    : invisibles.forEach(i => i.classList.add('invisible'));
});
