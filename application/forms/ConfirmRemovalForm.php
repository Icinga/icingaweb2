<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use Icinga\Web\Form;

/**
 * Form for confirming removal of an object
 */
class ConfirmRemovalForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_confirm_removal');
        $this->getSubmitLabel() ?: $this->setSubmitLabel($this->translate('Confirm Removal'));
    }
}
