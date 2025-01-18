<header class="relative bg-blue-700 shadow-2xl shadow-gray-500 before:absolute before:w-6 before:h-6 before:left-0 before:-bottom-6 before:shadow-[-0.25rem_-0.25rem_0_0.25rem_#093eba] before:rounded-tl-[1.5rem] after:absolute after:w-6 after:h-6 after:right-0 after:-bottom-6 after:shadow-[0.25rem_-0.25rem_0_0.25rem_#0037ad] after:rounded-tr-[3rem]">

    <?php require_once('menu_top.php'); ?>
    <?php require_once('menu_main.php'); ?>

    <!-- Set margin bottom if cards are present -->
    <?php
    $cardPages = ['client'];
    foreach ($cardPages as $page) {
        if (str_contains($request_uri, $page)) {
            $cardmb = 'mb-20';
            break;
        } else {
            $cardmb = 'mb-24';
            break;
        }
    }
    ?>
    <div class="container">
        <div class="<?= $cardmb ?> w-full mt-8 inline-flex items-center justify-between">
            <h1 class="text-white/90">
                <?= (isset($title) ? $title : null) ? (isset($title) ? $this->Html->safe($title) : null) : $this->_('AppController.client_structure.default_title', true); ?>
            </h1>

            <?php require_once('menu_area.php') ?>
        </div>
    </div>

    <section class="cards h-0 overflow-clip container flex flex-col items-center relative bg-white rounded-sm lg:flex-row gap-x-8 gap-y-4"></section>
</header>


<?php
$this->Javascript->setInline('
    $(document).ready(function() {        
        $(".cards").blestaLoadCards({
            fetch_uri: "' . $this->Html->safe($this->base_uri . 'main/getcards/' . (isset($client->id) ? $client->id : null), true) . '",
        });
    });

    setTimeout(function() {
            var cardsElement = document.querySelector(".cards");
                if (cardsElement && cardsElement.innerHTML.trim() !== "") {
                cardsElement.classList.remove("hidden", "h-0");
                cardsElement.classList.add("flex", "-mt-16", "p-4", "translate-y-14", "h-auto", "transition-all");
            }
    }, 1000);
');
?>
