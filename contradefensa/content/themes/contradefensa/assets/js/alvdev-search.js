new GhostFinder({
  input: '#search-input',
  showResult: '#search-result',
  contentApiKey: '8f8b77ced0e6b2f4552ad98eb9',
  excerpt_length: 150,
  singleResultTemplate: `
      <li>
        <a href="##url">##title</a>
        <p>##excerpt</p>
      </li>
    `,
});

const searchBtn = document.querySelector('#search-btn');
const searchInput = document.querySelector('#search-input');
const menu = document.querySelector('.gh-head-menu');
const searchResult = document.querySelector('#search-result');

searchBtn.addEventListener('click', e => {
  e.preventDefault();
  searchInput.classList.add('opened');
  menu.style.display = 'none';
  searchInput.focus();
});

searchInput.addEventListener('focusout', e => {
  searchInput.classList.remove('opened');
  menu.style.display = 'flex';
  searchResult.innerHTML = '';
});
