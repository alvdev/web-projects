<div class="alv-linear-gradient-white">
    <div class="container items-center justify-between py-3 text-sm bg-opacity-50 topnav md:flex">
        <div class="justify-between hidden gap-4 md:inline-flex">
            <span>
                +1 (307) 622-1377
            </span>
            <span>
                Chat en vivo
            </span>
            <div>
                <?php if ((isset($staff_as_client) ? $staff_as_client : null)): ?>
                    <span class="badge badge-pill badge-info float-right"><a
                            href="<?= $this->Html->safe($this->admin_uri . 'clients/logoutasclient/'); ?>"><span
                                class="fas fa-info-circle"></span>
                            <?php $this->_('AppController.client_structure.staff_as_client_note'); ?></a></span>
                <?php elseif ((isset($show_language) ? $show_language : null) && count((isset($languages) ? $languages : [])) > 1): ?>
                    <div>
                        <?php
                        $this->Form->create($this->client_uri . 'main/setlanguage/', ['id' => 'language_selector']);
                        $this->Form->fieldHidden('redirect_uri', (isset($request_uri) ? $request_uri : null));
                        $this->Form->fieldHidden('language_code', Configure::get('Blesta.language'), ['id' => 'language_code']);
                        $this->Form->end();
                        ?>
                        <button popovertarget="language_switcher" class="[anchor-name:--lang-btn]">
                            <?= (isset($languages[Configure::get('Blesta.language')]) ? $this->Html->safe($languages[Configure::get('Blesta.language')]) : null); ?>
                            <div class="font-black float-right ml-0.5">▾</div>
                        </button>

                        <div popover id="language_switcher" class="absolute [position-anchor:--lang-btn] [top:anchor(bottom)] [left:anchor(left)] mt-2">
                            <?php foreach ($languages as $code => $language): ?>
                                <a href="#" class="language_code" language_code="<?= $this->Html->safe($code); ?>"><?= $this->Html->safe($language); ?></a>
                            <?php endforeach ?>
                        </div>
                    </div>
                <?php endif ?>
            </div>
        </div>
        <div class="grid justify-between grid-flow-col gap-4 text-center md:inline-flex">
            <a href="./" class="flex items-center gap-2 px-3 py-1 text-white rounded-xs">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-envelope-fill" viewBox="0 0 16 16">
                    <path d="M.05 3.555A2 2 0 0 1 2 2h12a2 2 0 0 1 1.95 1.555L8 8.414.05 3.555zM0 4.697v7.104l5.803-3.558L0 4.697zM6.761 8.83l-6.57 4.027A2 2 0 0 0 2 14h12a2 2 0 0 0 1.808-1.144l-6.57-4.027L8 9.586l-1.239-.757zm3.436-.586L16 11.801V4.697l-5.803 3.546z"></path>
                </svg>
                Web mail
            </a>
            <a href="./" class="flex items-center gap-2 px-3 py-1 text-white rounded-xs">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-gear-fill" viewBox="0 0 16 16">
                    <path d="M9.405 1.05c-.413-1.4-2.397-1.4-2.81 0l-.1.34a1.464 1.464 0 0 1-2.105.872l-.31-.17c-1.283-.698-2.686.705-1.987 1.987l.169.311c.446.82.023 1.841-.872 2.105l-.34.1c-1.4.413-1.4 2.397 0 2.81l.34.1a1.464 1.464 0 0 1 .872 2.105l-.17.31c-.698 1.283.705 2.686 1.987 1.987l.311-.169a1.464 1.464 0 0 1 2.105.872l.1.34c.413 1.4 2.397 1.4 2.81 0l.1-.34a1.464 1.464 0 0 1 2.105-.872l.31.17c1.283.698 2.686-.705 1.987-1.987l-.169-.311a1.464 1.464 0 0 1 .872-2.105l.34-.1c1.4-.413 1.4-2.397 0-2.81l-.34-.1a1.464 1.464 0 0 1-.872-2.105l.17-.31c.698-1.283-.705-2.686-1.987-1.987l-.311.169a1.464 1.464 0 0 1-2.105-.872l-.1-.34zM8 10.93a2.929 2.929 0 1 1 0-5.86 2.929 2.929 0 0 1 0 5.858z"></path>
                </svg>
                cPanel
            </a>

            <a href="<?= $this->Html->safe($this->base_uri . 'order/cart/index/' . ($order_form->label ?? null)); ?>" class="flex items-center gap-2 px-3 py-1 text-white rounded-xs">
                <i class="fas fa-shopping-cart"></i> <?php $this->_('Main.packages.show_cart_btn'); ?>
            </a>
            <?php require_once('menu_login.php'); ?>
        </div>
    </div>
</div>
