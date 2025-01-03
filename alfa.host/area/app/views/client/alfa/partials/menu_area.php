<nav class="container flex flex-col items-center relative translate-y-2/4 px-8 py-8 -mt-4 mb-32 bg-gray-200 rounded-xs lg:flex-row gap-x-8 gap-y-4">
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#main-navbar">
        <span class="navbar-toggler-icon"></span>
    </button>
    <div class="w-full" id="main-navbar">
        <div class="flex justify-between">
            <?php $active_nav = null; ?>
            <ul class="flex gap-4">
                <?php foreach ((isset($nav) ? $nav : []) as $link => $value):
                    $active = false;
                    if ($value['active']) {
                        $active = true;
                        $attributes['class'][] = 'active';
                        $active_nav = $value;
                    }

                    // Build dropdown
                    $dropdown = false;
                    if (
                        isset($value['sub'])
                        && is_array($value['sub'])
                        && (count($value['sub']) > 1 || !array_key_exists($link, (array)$value['sub']))
                    ) {
                        $dropdown = true;
                        // Set parent to active if child is
                        if (!$active) {
                            foreach ((isset($value['sub']) ? $value['sub'] : []) as $sub_link => $sub_value) {
                                if ($sub_value['active']) {
                                    $attributes['class'][] = 'active';
                                    break;
                                }
                            }
                        }
                    }
                ?>

                    <li <?= $this->Html->buildAttributes($attributes) ?>>
                        <?php if (!$dropdown): ?>
                            <a href="<?php (print(isset($link) ? $this->Html->safe($link) : null)) ?>"
                                <?= $this->Html->buildAttributes($link_attributes) ?>>
                                <i
                                    class="<?php (print(isset($value['icon']) ? $this->Html->safe($value['icon']) : null)); ?>"></i>
                                <?php (print(isset($value['name']) ? $this->Html->safe($value['name']) : null)) ?>

                            </a>
                        <?php else: ?>
                            <button popovertarget="menu<?= $value['id']; ?>" href="<?php (print(isset($link) ? $this->Html->safe($link) : null)) ?>"
                                <?= $this->Html->buildAttributes($link_attributes) ?>>
                                <i
                                    class="<?php (print(isset($value['icon']) ? $this->Html->safe($value['icon']) : null)); ?>"></i>
                                <?php (print(isset($value['name']) ? $this->Html->safe($value['name']) : null)) ?>

                            </button>
                        <?php endif ?>

                        <?php if ($dropdown): ?>
                            <div popover id="menu<?= $value['id']; ?>" class="[&:popover-open]:absolute [&:popover-open]:flex [&:popover-open]:flex-col p-6 gap-8 rounded-sm border-2">
                                <?php foreach ((isset($value['sub']) ? $value['sub'] : []) as $sub_link => $sub_value): ?>
                                    <a class="dropdown-item"
                                        href="<?php (print(isset($sub_link) ? $this->Html->safe($sub_link) : null)); ?>"><i
                                            class="<?php (print(isset($sub_value['icon']) ? $this->Html->safe($sub_value['icon']) : null)); ?>"></i>
                                        <?php (print(isset($sub_value['name']) ? $this->Html->safe($sub_value['name']) : null)); ?></a>
                                <?php endforeach ?>
                            </div>
                        <?php endif ?>
                    </li>
                <?php endforeach ?>
            </ul>

            <ul class="navbar-nav">
                <?php if ((isset($logged_in) ? $logged_in : null)): ?>
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                            <?php (print(isset($contact->first_name) ? $this->Html->safe($contact->first_name) : null)); ?>
                            <?php (print(isset($contact->last_name) ? $this->Html->safe($contact->last_name) : null)); ?>
                            <b class="caret"></b>
                        </a>
                        <div class="dropdown-menu">
                            <a class="dropdown-item"
                                href="<?= $this->Html->safe($this->client_uri . 'main/edit/'); ?>"><i
                                    class="fas fa-edit fa-fw"></i>
                                <?php $this->_('AppController.client_structure.text_update_account'); ?></a>
                            <a class="dropdown-item"
                                href="<?= $this->Html->safe($this->client_uri . 'accounts/'); ?>"><i
                                    class="fas fa-credit-card fa-fw"></i>
                                <?php $this->_('AppController.client_structure.text_accounts'); ?></a>
                            <a class="dropdown-item"
                                href="<?= $this->Html->safe($this->client_uri . 'contacts/'); ?>"><i
                                    class="fas fa-users fa-fw"></i>
                                <?php $this->_('AppController.client_structure.text_contacts'); ?></a>
                            <?php if (!(isset($is_manager) ? $is_manager : null)): ?>
                                <a class="dropdown-item"
                                    href="<?= $this->Html->safe($this->client_uri . 'managers/'); ?>"><i
                                        class="fas fa-user-secret fa-fw"></i>
                                    <?php $this->_('AppController.client_structure.text_managers'); ?></a>
                            <?php endif; ?>

                            <?php if ((isset($has_email_permission) ? $has_email_permission : null)): ?>
                                <a class="dropdown-item" href="<?= $this->Html->safe($this->client_uri . 'emails/'); ?>"><i
                                        class="fas fa-inbox fa-fw"></i>
                                    <?php $this->_('AppController.client_structure.text_emails'); ?></a>
                            <?php endif; ?>

                            <?php if ((isset($portal_installed) ? $portal_installed : false)): ?>
                                <a class="dropdown-item" href="<?= $this->Html->safe(WEBDIR); ?>"><i
                                        class="far fa-circle fa-fw"></i>
                                    <?php $this->_('AppController.client_structure.text_return_to_portal'); ?></a>
                            <?php endif ?>

                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="<?= $this->Html->safe($this->client_uri . 'logout/'); ?>"><i
                                    class="fas fa-sign-out-alt fa-fw"></i>
                                <?php $this->_('AppController.client_structure.text_logout'); ?></a>
                        </div>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= $this->Html->safe($this->client_uri . 'login/'); ?>">
                            <?php $this->_('AppController.client_structure.text_login'); ?>
                        </a>
                    </li>
                <?php endif ?>
            </ul>
        </div>
    </div>
</nav>
