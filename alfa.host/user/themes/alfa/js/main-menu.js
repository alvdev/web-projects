function stickyNav() {
  const nav = document.querySelector('nav');
  const navLinks = document.querySelectorAll('nav ul a');

  if (scrollY > 50) {
    nav.classList.add('sticky');

    for (const link of navLinks) {
      link.classList.remove('lg:text-white');
    }
  } else {
    nav.classList.remove('sticky');

    for (const link of navLinks) {
      link.classList.add('lg:text-white');
    }
  }
}
document.addEventListener('scroll', stickyNav);

function burgerMenu() {
  const burger = document.querySelector('#burger');
  const menu = document.querySelector('#burger ~ ul');
  //const menuLinks = document.querySelectorAll('#burger ~ ul a');
  const cross = document.querySelector('#burger span');

  burger.addEventListener('click', () => {
    //burger.classList.toggle('mt-2'); // No needed after moving html container
    cross.innerText = cross.innerText === '☰' ? '✕' : '☰';
    menu.classList.toggle('hidden');
    menu.classList.add('bg-opacity-90');

    document.addEventListener('scroll', () => {
      scrollY < 50
        ? menu.classList.add('bg-white', 'mt-6')
        : menu.classList.remove('bg-white', 'mt-6');
    });
  });

  document.addEventListener('scroll', () => {
    scrollY > 50
      ? cross.classList.remove('text-white')
      : cross.classList.add('text-white');
  });

  //for (link of menuLinks) link.classList.toggle('lg:text-black');
}
burgerMenu();

function megamenu() {
  const menuItems = document.querySelectorAll('#main-menu a');
  const submenuItems = document.querySelectorAll('[id$="-submenu"]');

  const closeMenuItemsContent = () => {
    submenuItems.forEach(item => {
      item.classList.add('hidden');
      item.querySelector('.close-submenu').addEventListener('click', () => {
        item.classList.add('hidden');
      });
    });
  };

  for (let item of menuItems) {
    item.addEventListener('click', e => {
      if (item.id === 'hosting') {
        e.preventDefault();
        closeMenuItemsContent();
        document.querySelector('#hosting-submenu').classList.remove('hidden');
      }
      if (item.id === 'dominios') {
        e.preventDefault();
        closeMenuItemsContent();
        document.querySelector('#domains-submenu').classList.remove('hidden');
      }
      if (item.id === 'marketing') {
        e.preventDefault();
        closeMenuItemsContent();
        document.querySelector('#marketing-submenu').classList.remove('hidden');
      }
      if (item.id === 'recursos') {
        e.preventDefault();
        closeMenuItemsContent();
        document.querySelector('#resources-submenu').classList.remove('hidden');
      }
    });
  }
}
megamenu();
