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
        $element = $this->getElement();
        $description = $element->getDescription();
        $requirement = $element->getAttrib('requirement');
        unset($element->requirement);

        $helpContent = '';
        if ($description || $requirement) {
            if ($this->accessible) {
                $helpContent = '<span id="'
                    . $this->getDescriptionId()
                    . '" class="sr-only">'
                    . $description
                    . ($description && $requirement ? ' ' : '')
                    . $requirement
                    . '</span>';
            }

            $helpContent = $this->getView()->icon(
                'help',
                $description . ($description && $requirement ? ' ' : '') . $requirement,
                array('aria-hidden' => $this->accessible ? 'true' : 'false')
            ) . $helpContent;
        }

        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $helpContent;
            case self::PREPEND:
                return $helpContent . $content;
        }
    }
}
