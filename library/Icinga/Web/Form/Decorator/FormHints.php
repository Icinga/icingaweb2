<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Form\Decorator;

use Zend_Form_Decorator_Abstract;
use Icinga\Web\Form;

/**
 * Decorator to add a list of hints at the top or bottom of a form
 */
class FormHints extends Zend_Form_Decorator_Abstract
{
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

        $hints = $this->recurseForm($form);

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
     *
     * @return  array
     */
    protected function recurseForm(Form $form)
    {
        $hints = array($form->getHints());
        foreach ($form->getSubForms() as $subForm) {
            $hints[] = $this->recurseForm($subForm);
        }

        return call_user_func_array('array_merge', $hints);
    }
}
