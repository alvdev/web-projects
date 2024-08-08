import Alpine from 'alpinejs';
import ajax from '@imacrayon/alpine-ajax';
import Prism from 'prismjs';
import 'prismjs/components/prism-css';

window.Alpine = Alpine;
Alpine.plugin(ajax);

// Load more articles
document.addEventListener('alpine:init', () => {
  Alpine.data('loadMore', () => ({
    items: [],
    page: 1,
    perPage: 3,
    hasMore: true,

    async loadMoreItems() {
      const response = await fetch(`/blog/page:${this.page}`);
      const newPages = await response.text();

      const newItems = () => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(newPages, 'text/html');
        const articles = doc.querySelectorAll('#results article');
        return articles;
      };
      const articles = newItems();
      articles.forEach(article => console.log(article));

      this.items = [...this.items, ...articles];
      this.page++;

      if (articles.length < this.perPage) {
        this.hasMore = false;
      }
    },

    async init() {
      await this.loadMoreItems();
    },
  }));
});

Alpine.start();
