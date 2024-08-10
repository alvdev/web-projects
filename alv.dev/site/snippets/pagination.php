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
            <?php if ($pagination->hasPrevPage()) : ?>
                <a class="pagination-prev hover:text-lime-500" href="<?= $pagination->prevPageUrl() ?>#content">🡰</a>
            <?php else : ?>
                <span class="pagination-prev text-gray-400">🡰</span>
            <?php endif ?>

            <?php if ($pagination->hasNextPage()) : ?>
                <a class="pagination-next hover:text-lime-500" href="<?= $pagination->nextPageUrl() ?>#content">🡲</a>
            <?php else : ?>
                <span class="pagination-next text-gray-400">🡲</span>
            <?php endif ?>
        </nav>
    <?php endif ?>
</div>
