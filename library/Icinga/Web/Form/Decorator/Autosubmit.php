<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Element;
use Zend_Form_Decorator_Abstract;
use Icinga\Application\Icinga;
use Icinga\Web\View;

/**
 * Decorator to add an icon and a submit button encapsulated in noscript-tags
 *
 * The icon is shown in JS environments to indicate that a specific form control does automatically request an update
 * of its form upon it has changed. The button allows users in non-JS environments to trigger the update manually.
 */
class Autosubmit extends Zend_Form_Decorator_Abstract
{
    /**
     * Whether a hidden <span> should be created with the same warning as in the icon label
     *
     * @var bool
     */
    protected $accessible = false;

    /**
     * The id used to identify the auto-submit warning associated with the decorated form element
     *
     * @var string
     */
    protected $warningId;

    /**
     * Set whether a hidden <span> should be created with the same warning as in the icon label
     *
     * @param   bool    $state
     *
     * @return  Autosubmit
     */
    public function setAccessible($state = true)
    {
        $this->accessible = (bool) $state;
        return $this;
    }

    /**
     * Return the id used to identify the auto-submit warning associated with the decorated element
     *
     * @param   Zend_Form_Element   $element    The element for which to generate a id
     *
     * @return  string
     */
    public function getWarningId(Zend_Form_Element $element = null)
    {
        if ($this->warningId === null) {
            $element = $element ?: $this->getElement();
            $this->warningId = 'autosubmit_warning_' . $element->getId();
        }

        return $this->warningId;
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
     * Add a auto-submit icon and submit button encapsulated in noscript-tags to the element
     *
     * @param   string      $content    The html rendered so far
     *
     * @return  string                  The updated html
     */
    public function render($content = '')
    {
        if ($content) {
            $warning = t('Upon its value has changed, this control issues an automatic update of this page.');
            $content .= $this->getView()->icon('cw', $warning, array(
                'aria-hidden'   => 'true',
                'class'         => 'autosubmit-warning'
            ));
            if ($this->accessible) {
                $content = '<span id="' . $this->getWarningId() . '" class="sr-only">' . $warning . '</span>' . $content;
            }

            $content .= sprintf(
                '<noscript><button'
                . ' name="noscript_apply"'
                . ' class="noscript-apply"'
                . ' type="submit"'
                . ' value="1"'
                . ($this->accessible ? ' aria-label="%1$s"' : '')
                . ' title="%1$s"'
                . '>%2$s</button></noscript>',
                t('Push this button to update the form to reflect the change that was made in the control on the left'),
                $this->getView()->icon('cw') . t('Apply')
            );
        }

        return $content;
    }
}
