new GhostFinder({
  input: '#search-input',
  showResult: '#search-result',
  contentApiKey: '8f8b77ced0e6b2f4552ad98eb9',
  excerpt_length: 150,
  singleResultTemplate: `
      <li>
        <h4><a href="##url">##title</a></h4>
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
  e.stopImmediatePropagation();
  searchInput.classList.add('opened');
  menu.style.display = 'none';
  searchInput.focus();

  closeResults();
});

const closeResults = () => {
  document.addEventListener('click', e => {
    if (
      !e.target.closest('.search-results-wrapper') &&
      !e.target.closest('#search-input')
    )
      closeActions();
  });

  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') closeActions();
  });
};

const closeActions = () => {
  searchInput.value = '';
  searchInput.classList.remove('opened');
  menu.style.display = 'flex';
  searchResult.innerHTML = '';
};
