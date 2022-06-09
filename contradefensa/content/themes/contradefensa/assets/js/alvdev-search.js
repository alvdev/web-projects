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
