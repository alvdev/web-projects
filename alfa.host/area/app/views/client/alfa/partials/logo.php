<?php if (!empty($theme_logo)): ?>
    <img <?= (!empty($client_logo_height) ? ' style="height: ' . $client_logo_height . 'px;"' : ''); ?> src="<?php (print(isset($theme_logo) ? $this->Html->safe($theme_logo) : null)); ?>"
        alt="<?= (isset($system_company->name) ? $system_company->name : null); ?>" />
<?php elseif (!empty($blesta_logo)): ?>
    <img src="<?php (print(isset($blesta_logo) ? $this->Html->safe($this->view_dir . '/images/logo.svg') : null)); ?>"
        alt="Hosting para emprendedores - Alfa Host" />
<?php else: ?>
    <img <?= (!empty($client_logo_height) ? ' style="height: ' . $client_logo_height . 'px;"' : ''); ?> class="blesta"
        src="<?= $this->view_dir; ?>images/logo.svg"
        alt="Blesta" />
<?php endif ?>