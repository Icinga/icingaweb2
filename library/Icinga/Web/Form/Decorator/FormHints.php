<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;
use Icinga\Web\Form;

/**
 * Decorator to add a list of hints at the top or bottom of a form
 *
 * The hint for required form elements is automatically being handled.
 */
class FormHints extends Zend_Form_Decorator_Abstract
{
    /**
     * A list of element class names to be ignored when detecting which message to use to describe required elements
     *
     * @var array
     */
    protected $blacklist;

    /**
     * {@inheritdoc}
     */
    public function __construct($options = null)
    {
        parent::__construct($options);
        $this->blacklist = array(
            'Zend_Form_Element_Hidden',
            'Zend_Form_Element_Submit',
            'Zend_Form_Element_Button',
            'Icinga\Web\Form\Element\Note',
            'Icinga\Web\Form\Element\Button',
            'Icinga\Web\Form\Element\CsrfCounterMeasure'
        );
    }

    /**
     * Render form hints
     *
     * @param   string      $content    The html rendered so far
     *
     * @return  string                  The updated html
     */
    public function render($content = '')
    {
        $form = $this->getElement();
        if (! $form instanceof Form) {
            return $content;
        }

        $view = $form->getView();
        if ($view === null) {
            return $content;
        }

        $hints = $this->recurseForm($form, $entirelyRequired);
        if ($entirelyRequired !== null) {
            $hints[] = sprintf(
                $form->getView()->translate('%s Required field'),
                $form->getRequiredCue()
            );
        }

        if (empty($hints)) {
            return $content;
        }

        $html = '<ul class="form-info">';
        foreach ($hints as $hint) {
            if (is_array($hint)) {
                list($hint, $properties) = $hint;
                $html .= '<li' . $view->propertiesToString($properties) . '>' . $view->escape($hint) . '</li>';
            } else {
                $html .= '<li>' . $view->escape($hint) . '</li>';
            }
        }

        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $html . '</ul>';
            case self::PREPEND:
                return $html . '</ul>' . $content;
        }
    }

    /**
     * Recurse the given form and return the hints for it and all of its subforms
     *
     * @param   Form    $form               The form to recurse
     * @param   mixed   $entirelyRequired   Set by reference, true means all elements in the hierarchy are
     *                                       required, false only a partial subset and null none at all
     * @param   bool    $elementsPassed     Whether there were any elements passed during the recursion until now
     *
     * @return  array
     */
    protected function recurseForm(Form $form, & $entirelyRequired = null, $elementsPassed = false)
    {
        $requiredLabels = array();
        if ($form->getRequiredCue() !== null) {
            $partiallyRequired = $partiallyOptional = false;
            foreach ($form->getElements() as $element) {
                if (! in_array($element->getType(), $this->blacklist)) {
                    if (! $element->isRequired()) {
                        $partiallyOptional = true;
                        if ($entirelyRequired) {
                            $entirelyRequired = false;
                        }
                    } else {
                        $partiallyRequired = true;
                        if (($label = $element->getDecorator('label')) !== false) {
                            $requiredLabels[] = $label;
                        }
                    }
                }
            }

            if (! $elementsPassed) {
                $elementsPassed = $partiallyRequired || $partiallyOptional;
                if ($entirelyRequired === null && $partiallyRequired) {
                    $entirelyRequired = ! $partiallyOptional;
                }
            } elseif ($entirelyRequired === null && $partiallyRequired) {
                $entirelyRequired = false;
            }
        }

        $hints = array($form->getHints());
        foreach ($form->getSubForms() as $subForm) {
            $hints[] = $this->recurseForm($subForm, $entirelyRequired, $elementsPassed);
        }

        if ($entirelyRequired) {
            foreach ($requiredLabels as $label) {
                $label->setRequiredSuffix('');
            }
        }

        return call_user_func_array('array_merge', $hints);
    }
}
