<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Decorator;

use Icinga\Application\Icinga;
use Zend_Form_Decorator_Abstract;

/**
 * A decorator that will display the description as a help icon
 */
class Help extends Zend_Form_Decorator_Abstract
{
    /**
     * Render a description and show it as a help icon
     *
     * @param  string $content
     * @return string
     */
    public function render($content = '')
    {
        $element = $this->getElement();
        $description = $element->getView()->escape($element->getDescription());

        if (! empty($description)) {
            $helpIcon = Icinga::app()->getViewRenderer()->view->icon('help', $description);
            return $helpIcon . $content;
        }

        return  $content;
    }
}
