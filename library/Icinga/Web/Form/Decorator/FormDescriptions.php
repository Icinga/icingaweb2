<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;
use Icinga\Web\Form;

/**
 * Decorator to add a list of descriptions at the top of a form
 *
 * The description for required form elements is automatically being handled.
 */
class FormDescriptions extends Zend_Form_Decorator_Abstract
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
     * Render form descriptions
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

        $descriptions = $this->recurseForm($form, $entirelyRequired);
        if ($entirelyRequired) {
            $descriptions[] = $form->getView()->translate(
                'All fields are required and must be filled in to complete the form.'
            );
        } elseif ($entirelyRequired === false) {
            $descriptions[] = $form->getView()->translate(sprintf(
                'Required fields are marked with %s and must be filled in to complete the form.',
                $form->getRequiredCue()
            ));
        }

        if (empty($descriptions)) {
            return $content;
        }

        $html = '<ul class="descriptions">';
        foreach ($descriptions as $description) {
            if (is_array($description)) {
                list($description, $properties) = $description;
                $html .= '<li' . $view->propertiesToString($properties) . '>' . $view->escape($description) . '</li>';
            } else {
                $html .= '<li>' . $view->escape($description) . '</li>';
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
     * Recurse the given form and return the descriptions for it and all of its subforms
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

        $descriptions = array($form->getDescriptions());
        foreach ($form->getSubForms() as $subForm) {
            $descriptions[] = $this->recurseForm($subForm, $entirelyRequired, $elementsPassed);
        }

        if ($entirelyRequired) {
            foreach ($requiredLabels as $label) {
                $label->setRequiredSuffix('');
            }
        }

        return call_user_func_array('array_merge', $descriptions);
    }
}
