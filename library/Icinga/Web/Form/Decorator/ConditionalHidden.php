<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;

/**
 * Decorator to hide elements using a &gt;noscript&lt; tag instead of
 * type='hidden' or css styles.
 *
 * This allows to hide depending elements for browsers with javascript
 * (who can then automatically refresh their pages) but show them in
 * case JavaScript is disabled
 */
class ConditionalHidden extends Zend_Form_Decorator_Abstract
{
    /**
     * Generate a field that will be wrapped in <noscript> tag if the
     * "condition" attribute is set and false or 0
     *
     * @param   string $content The tag's content
     *
     * @return  string The generated tag
     */
    public function render($content = '')
    {
        $attributes = $this->getElement()->getAttribs();
        $condition = isset($attributes['condition']) ? $attributes['condition'] : 1;
        if ($condition != 1) {
            $content = '<noscript>' . $content . '</noscript>';
        }
        return $content;
    }
}
