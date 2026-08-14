<?php

/**
 * @var tobimori\DreamForm\Models\Submission|null $submission
 *
 * @var \Kirby\Cms\Block $block
 * @var \Kirby\Cms\Field $field
 * @var \tobimori\DreamForm\Fields\ButtonField $field
 * @var \tobimori\DreamForm\Models\FormPage $form
 * @var array $attr
 */

use Kirby\Toolkit\A;
use tobimori\DreamForm\DreamForm;
use tobimori\DreamForm\Support\Htmx;

if (
	// Output guards before the last button field of the current step
	// so that the context is right for captcha guards
	($buttonFields = $form->formFields(
		$submission?->form()->is($form) ? $submission?->currentStep() ?? 1 : 1
	)->filterBy('type', 'button'))
	&& $buttonFields->last() === $field
) {
	// Show form-level errors (e.g. captcha) right above the captcha widget
	$formError = $submission?->errorFor(null, $form);
	$hideEmptyErrors = DreamForm::option('hideEmptyErrors');
	if (!$hideEmptyErrors || $formError) : ?>
		<div <?= attr(A::merge(['data-error' => true, 'role' => 'alert', 'aria-atomic' => 'true'], $attr['error'])) ?>><?= $formError ?></div>
	<?php endif;

	snippet('dreamform/guards', compact('form', 'attr'));
}

snippet('dreamform/fields/partials/wrapper', compact('block', 'field', 'form', 'attr'), slots: true) ?>

<button <?= attr(A::merge($attr['button'] ?? [], [
	'type' => 'submit'
])) ?>>
	<?= $block->label()->or(t('dreamform.fields.button.label.label'))->escape() ?>
</button>

<?php endsnippet() ?>
