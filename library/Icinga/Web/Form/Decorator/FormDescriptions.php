<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Decorator;

use Icinga\Application\Icinga;
use Icinga\Web\Form;
use Zend_Form_Decorator_Abstract;

/**
 * Decorator to add a list of descriptions at the top or bottom of a form
 */
class FormDescriptions extends Zend_Form_Decorator_Abstract
{
    /**
     * Render form descriptions
     *
     * @param   string      $content    The html rendered so far
     *
     * @return  ?string                  The updated html
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

        $descriptions = $this->recurseForm($form);
        if (empty($descriptions)) {
            return $content;
        }

        $html = '<div class="form-description">'
            . Icinga::app()->getViewRenderer()->view->icon('info-circled', '', ['class' => 'form-description-icon'])
            . '<ul class="form-description-list">';

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
                return $content . $html . '</ul></div>';
            case self::PREPEND:
                return $html . '</ul></div>' . $content;
        }
    }

    /**
     * Recurse the given form and return the descriptions for it and all of its subforms
     *
     * @param   Form    $form   The form to recurse
     *
     * @return  array
     */
    protected function recurseForm(Form $form)
    {
        $descriptions = array($form->getDescriptions());
        foreach ($form->getSubForms() as $subForm) {
            $descriptions[] = $this->recurseForm($subForm);
        }

        return call_user_func_array('array_merge', $descriptions);
    }
}
