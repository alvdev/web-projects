import Alpine from '../node_modules/alpinejs';
import collapse from '../node_modules/@alpinejs/collapse';
import intersect from '../node_modules/@alpinejs/intersect';
import ajax from '../node_modules/@imacrayon/alpine-ajax';

window.Alpine = Alpine;

Alpine.plugin(collapse);
Alpine.plugin(intersect);
Alpine.plugin(ajax);

Alpine.start();
