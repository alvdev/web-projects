import type { Alpine } from 'alpinejs';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';

export default (Alpine: Alpine) => {
    Alpine.plugin(collapse);
    Alpine.plugin(intersect);
}
