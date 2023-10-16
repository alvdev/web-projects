import Alpine from '../node_modules/alpinejs/dist/module.esm.js';

import collapse from '../node_modules/@alpinejs/collapse/dist/module.esm.js';
Alpine.plugin(collapse);

window.Alpine = Alpine;

Alpine.start();
