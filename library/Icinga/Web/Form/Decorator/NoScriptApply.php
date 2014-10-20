<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;
use Icinga\Application\Icinga;

/**
 * Decorator to add a submit button encapsulated in noscript-tags
 *
 * This enables users in non-JS environments to update the contents
 * of a form without the use of the main submit button.
 */
class NoScriptApply extends Zend_Form_Decorator_Abstract
{
    /**
     * Add a submit button encapsulated in noscript-tags to the element
     *
     * @param   string      $content    The html rendered so far
     *
     * @return  string                  The updated html
     */
    public function render($content = '')
    {
        if ($content) {
            $content .= '<noscript><button name="noscript_apply" style="margin-left: 0.5em;" type="submit" value="1">'
                . Icinga::app()->getViewRenderer()->view->icon('refresh.png') . '&nbsp;' . t('Apply')
                . '</button></noscript>';
        }

        return $content;
    }
}
