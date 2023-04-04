const darkElems = document.querySelectorAll(".dark");

window.addEventListener("scroll", () => {
  for (let i of darkElems) {
    const classes = i.dataset.color.split(" ");
    console.log(classes);
    window.scrollY >= i.offsetTop - window.innerHeight / 3 &&
    window.scrollY <= i.offsetTop + i.clientHeight - window.innerHeight / 3
      ? document.body.classList.add(...classes)
      : document.body.classList.remove(...classes);
  }
});
