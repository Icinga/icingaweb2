<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use Icinga\Web\Form;

/**
 * Form for confirming removal of an object
 */
class ConfirmRemovalForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_confirm_removal');
        $this->getSubmitLabel() ?: $this->setSubmitLabel($this->translate('Confirm Removal'));
    }

    /**
     * {@inheritdoc}
     */
    public function addSubmitButton()
    {
        parent::addSubmitButton();

        if (($submit = $this->getElement('btn_submit')) !== null) {
            $class = $submit->getAttrib('class');
            $submit->setAttrib('class', empty($class) ? 'autofocus' : $class . ' autofocus');
        }

        return $this;
    }
}
