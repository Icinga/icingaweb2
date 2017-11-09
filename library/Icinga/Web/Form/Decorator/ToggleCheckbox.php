<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */


namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;
use Icinga\Application\Icinga;
use Icinga\Web\View;

class ToggleCheckbox extends Zend_Form_Decorator_Abstract
{

    /**
     * Default placement: prepend
     * @var string
     */
    protected $_placement = 'PREPEND';

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
     * Create a checkbox for toggling content
     *
     * @param   string      $content    The html rendered so far
     *
     * @return  string                  The updated html
     */
    public function render($content = '')
    {
        $toggleCheckbox = '<input type="checkbox" class="toggle-checkbox" id="'
            . $this->getOption('id')
            . '">'
            . '<label for="'
            . $this->getOption('id')
            . '" class="toggle-checkbox-legend">'
            . $this->getOption('label')
            . '</label>'
            //. $this->getView()->icon('plus')
            ;

        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $toggleCheckbox;
            case self::PREPEND:
                return $toggleCheckbox . $content;
        }
    }
}
