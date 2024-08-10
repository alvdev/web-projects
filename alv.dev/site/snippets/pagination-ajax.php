<?php
/*
  Snippets are a great way to store code snippets for reuse
  or to keep your templates clean.

  The pagination snippet renders prev/next links in the
  blog, when articles spread across multiple pages

  More about snippets:
  https://getkirby.com/docs/guide/templates/snippets
*/
?>
<div class="mt-28">
    <?php if ($pagination->hasPages()) : ?>
        <nav id="pagination" class="relative z-10 text-xl font-bold text-center" x-data="{page: 1}">
            <!-- Load more -->
            <a class="rounded-full px-8 py-4 uppercase bg-gray-950 text-[#00ff77] inline-block transition-all hover:animate-wiggle" :href="'/blog/page:' + page + '#results'" x-on:click="page++" :class="page >= <?= $pagination->pages() ?> ? 'hidden' : ''" x-init x-target="results">
                <?= t('loadMore', 'Load more') ?>
            </a>
        </nav>
    <?php endif ?>
</div>
