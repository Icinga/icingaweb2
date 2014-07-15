<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;

/**
 * Decorator that automatically adds a helptext to an input element
 * when the 'helptext' attribute is set
 */
class HelpText extends Zend_Form_Decorator_Abstract
{
    /**
     * Add a helptext to an input field
     *
     * @param   string $content The help text
     *
     * @return  string The generated tag
     */
    public function render($content = '')
    {
        $attributes = $this->getElement()->getAttribs();
        $visible = true;
        if (isset($attributes['condition'])) {
            $visible = $attributes['condition'] == '1';
        }
        if (isset($attributes['helptext']) && $visible) {
            $content =  $content
                . '<p class="help-block">'
                . $attributes['helptext']
                . '</p>';
        }
        return $content;
    }
}
