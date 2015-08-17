<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;
use Icinga\Application\Icinga;
use Icinga\Web\View;
use Icinga\Web\Form;

/**
 * Decorator to add an icon and a submit button encapsulated in noscript-tags
 *
 * The icon is shown in JS environments to indicate that a specific form field does automatically request an update
 * of its form upon it has changed. The button allows users in non-JS environments to trigger the update manually.
 */
class Autosubmit extends Zend_Form_Decorator_Abstract
{
    /**
     * Whether a hidden <span> should be created with the same warning as in the icon label
     *
     * @var bool
     */
    protected $accessible;

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
     * Return whether a hidden <span> is being created with the same warning as in the icon label
     *
     * @return  bool
     */
    public function getAccessible()
    {
        if ($this->accessible === null) {
            $this->accessible = $this->getOption('accessible') ?: false;
        }

        return $this->accessible;
    }

    /**
     * Return the id used to identify the auto-submit warning associated with the decorated element
     *
     * @param   mixed   $element    The element for which to generate a id
     *
     * @return  string
     */
    public function getWarningId($element = null)
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
            $isForm = $this->getElement() instanceof Form;
            $warning = $isForm
                ? t('Upon any of this form\'s fields were changed, this page is being updated automatically.')
                : t('Upon its value has changed, this field issues an automatic update of this page.');
            $content .= $this->getView()->icon('cw', $warning, array(
                'aria-hidden'   => $isForm ? 'false' : 'true',
                'class'         => 'autosubmit-warning'
            ));
            if (! $isForm && $this->getAccessible()) {
                $content = '<span id="' . $this->getWarningId() . '" class="sr-only">' . $warning . '</span>' . $content;
            }

            $content .= sprintf(
                '<noscript><button'
                . ' name="noscript_apply"'
                . ' class="noscript-apply"'
                . ' type="submit"'
                . ' value="1"'
                . ($this->getAccessible() ? ' aria-label="%1$s"' : '')
                . ' title="%1$s"'
                . '>%2$s</button></noscript>',
                $isForm
                    ? t('Push this button to update the form to reflect the changes that were made below')
                    : t('Push this button to update the form to reflect the change'
                        . ' that was made in the field on the left'),
                $this->getView()->icon('cw') . t('Apply')
            );
        }

        return $content;
    }
}
