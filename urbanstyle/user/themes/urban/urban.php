<?php

namespace Grav\Theme;

use Grav\Common\Theme;

class Urban extends Theme
{
    public function onTwigInitialized()
    {
        $twig = $this->grav['twig'];

        $form_class_variables = [
            // 'form_outer_classes' => 'form-horizontal',
            'form_button_outer_classes' => 'flex gap-4 mt-12',
            // 'form_button_classes' => 'btn',
            // 'form_errors_classes' => '',
            // 'form_field_outer_classes' => 'form-group',
            // 'form_field_outer_label_classes' => 'form-label-wrapper',
            // 'form_field_label_classes' => 'form-label',
            // 'form_field_outer_data_classes' => 'col-9',
            // 'form_field_input_classes' => 'form-input',
            // 'form_field_textarea_classes' => '',
            // 'form_field_select_classes' => 'form-select',
            // 'form_field_radio_classes' => 'form-radio',
            // 'form_field_checkbox_classes' => 'form-checkbox',
        ];

        $twig->twig_vars = array_merge($twig->twig_vars, $form_class_variables);
    }
}
