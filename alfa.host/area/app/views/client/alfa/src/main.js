import Alpine from '../node_modules/alpinejs/dist/module.esm.min.js';
import collapse from '../node_modules/@alpinejs/collapse/dist/module.esm.min.js';
import './components.js';

window.Alpine = Alpine;

Alpine.plugin(collapse);

Alpine.start();
