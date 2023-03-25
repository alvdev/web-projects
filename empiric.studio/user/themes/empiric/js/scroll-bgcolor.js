const darkElems = document.querySelectorAll(".scrolldark");

window.addEventListener("scroll", () => {
    for (let i of darkElems) {
        window.scrollY >= i.offsetTop - window.innerHeight / 3 &&
        window.scrollY <= i.offsetTop + i.clientHeight - window.innerHeight / 3
            ? document.body.classList.add("bg-gray-900")
            : document.body.classList.remove("bg-gray-900");
    }
});
