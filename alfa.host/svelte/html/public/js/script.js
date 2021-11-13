// Functions

function stickyNav() {

  const nav = document.querySelector('nav');
  const navLinks = document.querySelectorAll('nav ul a');

  if (scrollY > 50) {
    nav.classList.add('sticky', 'fixed', 'top-0', 'py-0', 'w-full', 'backdrop-filter', 'backdrop-blur-md');

    for (const link of navLinks) {
      link.classList.remove('lg:text-white')
    }
  } else {
    nav.classList.remove('sticky', 'fixed', 'top-0', 'py-0', 'w-full');
    
    for (const link of navLinks) {
      link.classList.add('lg:text-white')
    }
  }

}
document.addEventListener('scroll', stickyNav);

burgerMenu();

function burgerMenu() {

  const burger = document.querySelector('#burger');
  const menu = document.querySelector('#burger ~ ul');
  //const menuLinks = document.querySelectorAll('#burger ~ ul a');
  const cross = document.querySelector('#burger span');
  //console.log(cross);

  burger.addEventListener('click', () => {
    //burger.classList.toggle('mt-2'); // No needed after moving html container
    cross.textContent == '☰' ? cross.textContent = '✕' : cross.textContent = '☰';
    menu.classList.toggle('hidden');
    menu.classList.add('bg-opacity-90');

    document.addEventListener('scroll', () => {
      if (scrollY < 50) {
        menu.classList.add('bg-white', 'mt-6');
      } else {
        menu.classList.remove('bg-white', 'mt-6');
      }
    })
  });

  document.addEventListener('scroll', () => {
    scrollY > 50 ? cross.classList.remove('text-white') : cross.classList.add('text-white');
  })

  //for (link of menuLinks) link.classList.toggle('lg:text-black');
}

document.addEventListener('scroll', toTop);

function toTop() {
  const toTop = document.querySelector('#totop');

  scrollY > 500 ? toTop.classList.remove('hidden') : toTop.classList.add('hidden')
}

servicesTabs();

function servicesTabs() {
  const tabs = document.querySelectorAll('.services ul > li > a');
  const tabsContent = document.querySelectorAll('.services div[id$="tabcontent"]');

  for (tab of tabs) {

    console.log(tab.classList.remove('active'));
    tab.addEventListener('click', e => {
      e.preventDefault();

      console.log(e.target.closest('.tab'));
      if (!e.target.closest('.tab').classList.contains('active')) {
        e.target.closest('.tab').classList.add('active');
      }

      // Getting id tag of a tab element to show tab content
      const id = e.target.closest('.tab').getAttribute('id');

      for (tabContent of tabsContent) {
        tabContent.getAttribute('id').includes(id) ?
          tabContent.classList.remove('hidden') :
          tabContent.classList.add('hidden')
      }
    });
  }
}
