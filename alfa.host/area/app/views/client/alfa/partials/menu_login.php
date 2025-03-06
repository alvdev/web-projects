<div class="flex items-center gap-2 relative">
    <?php if ((isset($logged_in) ? $logged_in : null)): ?>
        <button class="nav-link btn-outline bg-transparent bg-none rounded-full border-white text-white [&_svg]:fill-white hover:border-white hover:text-white hover:bg-white/10 [anchor-name:--user-menu-btn]" popovertarget="user-menu">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-people-fill" viewBox="0 0 16 16">
                <path d="M7 14s-1 0-1-1 1-4 5-4 5 3 5 4-1 1-1 1H7zm4-6a3 3 0 1 0 0-6 3 3 0 0 0 0 6z"></path>
                <path fill-rule="evenodd" d="M5.216 14A2.238 2.238 0 0 1 5 13c0-1.355.68-2.75 1.936-3.72A6.325 6.325 0 0 0 5 9c-4 0-5 3-5 4s1 1 1 1h4.216z"></path>
                <path d="M4.5 8a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z"></path>
            </svg>

            <?php (print(isset($contact->first_name) ? $this->Html->safe($contact->first_name) : null)); ?>
            <?php (print(isset($contact->last_name) ? $this->Html->safe($contact->last_name) : null)); ?>
        </button>

        <div id="user-menu"
            class="[position-anchor:--user-menu-btn] right-[anchor(right)] top-[anchor(bottom)] mt-2 min-w-max left-auto [&>a]:flex [&>a]:items-center [&>a]:gap-3" popover>
            <a href="<?= $this->Html->safe($this->client_uri . 'main/edit/'); ?>">
                <i class="fas fa-edit fa-fw"></i>
                <?php $this->_('AppController.client_structure.text_update_account'); ?>
            </a>

            <a href="<?= $this->Html->safe($this->client_uri . 'accounts/'); ?>">
                <i class="fas fa-credit-card fa-fw"></i>
                <?php $this->_('AppController.client_structure.text_accounts'); ?>
            </a>

            <a href="<?= $this->Html->safe($this->client_uri . 'contacts/'); ?>">
                <i class="fas fa-users fa-fw"></i>
                <?php $this->_('AppController.client_structure.text_contacts'); ?>
            </a>

            <?php if (!(isset($is_manager) ? $is_manager : null)): ?>
                <a href="<?= $this->Html->safe($this->client_uri . 'managers/'); ?>">
                    <i class="fas fa-user-secret fa-fw"></i>
                    <?php $this->_('AppController.client_structure.text_managers'); ?>
                </a>
            <?php endif; ?>

            <?php if ((isset($has_email_permission) ? $has_email_permission : null)): ?>
                <a href="<?= $this->Html->safe($this->client_uri . 'emails/'); ?>">
                    <i class="fas fa-inbox fa-fw"></i>
                    <?php $this->_('AppController.client_structure.text_emails'); ?>
                </a>
            <?php endif; ?>

            <?php if ((isset($portal_installed) ? $portal_installed : false)): ?>
                <a href="<?= $this->Html->safe(WEBDIR); ?>">
                    <i class="far fa-circle fa-fw"></i>
                    <?php $this->_('AppController.client_structure.text_return_to_portal'); ?>
                </a>
            <?php endif ?>

            <a href="<?= $this->Html->safe($this->client_uri . 'logout/'); ?>"
                class="border-t-2 pt-3 bg-rose-600 text-white uppercase font-semibold hover:bg-rose-700 border border-white rounded-sm">
                <i class="fas fa-sign-out-alt fa-fw"></i>
                <?php $this->_('AppController.client_structure.text_logout'); ?>
            </a>
        </div>
    <?php else: ?>
        <a class="nav-link btn-outline border-white text-white hover:border-white hover:text-white hover:bg-white/10" href="<?= $this->Html->safe($this->client_uri . 'login/'); ?>">
            <?php $this->_('AppController.client_structure.text_login'); ?>
        </a>
    <?php endif ?>
</div>
