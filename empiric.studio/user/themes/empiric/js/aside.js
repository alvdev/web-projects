const totop = document.querySelector("#totop");

document.addEventListener("scroll", () => {
  window.scrollY >= window.innerHeight / 1.5
    ? totop.classList.remove("invisible")
    : totop.classList.add("invisible");
});
