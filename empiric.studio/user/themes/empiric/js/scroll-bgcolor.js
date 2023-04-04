const sections = document.querySelectorAll("[data-color]");

window.addEventListener("scroll", () => {
  for (let section of sections) {
    const classes = section.dataset.color.split(" ");
    if (
      window.scrollY >= section.offsetTop - window.innerHeight / 3 &&
      window.scrollY <=
        section.offsetTop + section.clientHeight - window.innerHeight / 3
    ) {
      document.body.classList.add(...classes);
      break;
    } else {
      document.body.classList.remove(...classes);
    }
  }
});
