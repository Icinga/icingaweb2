<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;
use Icinga\Application\Icinga;
use Icinga\Web\View;

/**
 * Decorator to add a spinner next to an element
 */
class Spinner extends Zend_Form_Decorator_Abstract
{
    /**
     * Return the current view
     *
     * @return  View
     */
    protected function getView()
    {
        return Icinga::app()->getViewRenderer()->view;
    }

    /**
     * Add a spinner icon to a form element
     *
     * @param   string      $content    The html rendered so far
     *
     * @return  string                  The updated html
     */
    public function render($content = '')
    {
        $spinner = '<div '
            . ($this->getOption('id') !== null ? ' id="' . $this->getOption('id') . '"' : '')
            . 'class="spinner ' . ($this->getOption('class') ?: '') . '"'
            . '>'
            . $this->getView()->icon('spin6')
            . '</div>';

        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $spinner;
            case self::PREPEND:
                return $spinner . $content;
        }
    }
}
