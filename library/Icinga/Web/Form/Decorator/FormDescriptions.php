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

        $descriptions = $form->getDescriptions();
        if (($requiredDesc = $this->getRequiredDescription($form)) !== null) {
            $descriptions[] = $requiredDesc;
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
     * Return the description for the given form's required elements
     *
     * @param   Form    $form
     *
     * @return  string|null
     */
    protected function getRequiredDescription(Form $form)
    {
        if (($cue = $form->getRequiredCue()) === null) {
            return;
        }

        $requiredLabels = array();
        $entirelyRequired = true;
        $partiallyRequired = false;
        $blacklist = array(
            'Zend_Form_Element_Hidden',
            'Zend_Form_Element_Submit',
            'Zend_Form_Element_Button',
            'Icinga\Web\Form\Element\CsrfCounterMeasure'
        );
        foreach ($form->getElements() as $element) {
            if (! in_array($element->getType(), $blacklist)) {
                if (! $element->isRequired()) {
                    $entirelyRequired = false;
                } else {
                    $partiallyRequired = true;
                    if (($label = $element->getDecorator('label')) !== false) {
                        $requiredLabels[] = $label;
                    }
                }
            }
        }

        if ($entirelyRequired && $partiallyRequired) {
            foreach ($requiredLabels as $label) {
                $label->setRequiredSuffix('');
            }

            return $form->getView()->translate('All fields are required and must be filled in to complete the form.');
        } elseif ($partiallyRequired) {
            return $form->getView()->translate(sprintf(
                'Required fields are marked with %s and must be filled in to complete the form.',
                $cue
            ));
        }
    }
}
