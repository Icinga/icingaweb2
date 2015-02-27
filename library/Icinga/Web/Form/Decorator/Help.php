<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Element;
use Zend_Form_Decorator_Abstract;
use Icinga\Application\Icinga;
use Icinga\Web\View;

/**
 * Decorator to add helptext to a form element
 */
class Help extends Zend_Form_Decorator_Abstract
{
    /**
     * Whether a hidden <span> should be created to describe the decorated form element
     *
     * @var bool
     */
    protected $accessible = false;

    /**
     * The id used to identify the description associated with the decorated form element
     *
     * @var string
     */
    protected $descriptionId;

    /**
     * Set whether a hidden <span> should be created to describe the decorated form element
     *
     * @param   bool    $state
     *
     * @return  Help
     */
    public function setAccessible($state = true)
    {
        $this->accessible = (bool) $state;
        return $this;
    }

    /**
     * Return the id used to identify the description associated with the decorated element
     *
     * @param   Zend_Form_Element   $element    The element for which to generate a id
     *
     * @return  string
     */
    public function getDescriptionId(Zend_Form_Element $element = null)
    {
        if ($this->descriptionId === null) {
            $element = $element ?: $this->getElement();
            $this->descriptionId = 'desc_' . $element->getId();
        }

        return $this->descriptionId;
    }

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
     * Add a help icon to the left of an element
     *
     * @param   string      $content    The html rendered so far
     *
     * @return  string                  The updated html
     */
    public function render($content = '')
    {
        if ($content && ($description = $this->getElement()->getDescription()) !== null) {
            if ($this->accessible) {
                $content = '<span id="'
                    . $this->getDescriptionId()
                    . '" class="sr-only">'
                    . $description
                    . '</span>' . $content;
            }

            $content = $this->getView()->icon('help', $description, array('aria-hidden' => 'true')) . $content;
        }

        return $content;
    }
}
