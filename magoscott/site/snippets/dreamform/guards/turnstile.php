<?php

/**
 * @var tobimori\DreamForm\Models\FormPage $form
 * @var tobimori\DreamForm\Guards\TurnstileGuard $guard
 */

use tobimori\DreamForm\DreamForm;

?>
<div <?= attr([
            'x-data' => 'turnstileGuard',
            'class' => 'cf-turnstile',
            'data-theme' => DreamForm::option('guards.turnstile.theme', 'auto'),
            'data-sitekey' => $guard::siteKey(),
            'data-size' => 'compact',
        ]) ?>>
</div>
