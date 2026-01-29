import Alpine from 'alpinejs';
import ajax from '@imacrayon/alpine-ajax';
import focus from '@alpinejs/focus'
import intersect from '@alpinejs/intersect'

window.Alpine = Alpine;
Alpine.plugin(ajax);
Alpine.plugin(focus)
Alpine.plugin(intersect)



Alpine.start();
