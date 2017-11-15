<?php

namespace Drupal\wienimal_services\Service;

class MaxLengthService
{
    public $label = '@remaining / @limit';

    public function attach(array $element)
    {
        if (isset($element['#maxlength_js']) && $element['#maxlength_js'] === TRUE) {
            if (isset($element['#attributes']['maxlength']) && $element['#attributes']['maxlength'] > 0) {
                $element['#attributes']['maxlength_js_label'] = $this->label;
                $element['#attached']['library'][] = 'wienimal/maxlength.behaviors';
            }

            if (isset($element['summary']['#attributes']['maxlength']) && $element['summary']['#attributes']['maxlength'] > 0) {
                $element['summary']['#attributes']['maxlength_js_label'] = $this->label;
                $element['summary']['#attached']['library'][] = 'wienimal/maxlength.behaviors';
            }
        }

        return $element;
    }
}
