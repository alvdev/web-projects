<?php
/*
  Snippets are a great way to store code snippets for reuse
  or to keep your templates clean.

  In this snippet the svg() helper is a great way to embed SVG
  code directly in your HTML. Pass the path to your SVG
  file to load it.

  More about snippets:
  https://getkirby.com/docs/guide/templates/snippets
*/
?>
<div class="text-white">
  <span class="flex gap-4">
    <a href="https://linkedin.com/in/alvdev" target="_blank" class="[&>svg]:fill-white" aria-label="Sígueme en LinkedIn">
      <?= svg('assets/icons/linkedin.svg') ?>
    </a>
    <a href="https://github.com/alvdev" target="_blank" class="[&>svg]:!fill-white" aria-label="El Github de Alvaro Devesa">
      <?= svg('assets/icons/github.svg') ?>
    </a>
  </span>
</div>
