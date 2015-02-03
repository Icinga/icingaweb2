<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Forms;

use Icinga\Web\Form;

/**
 * Form for confirming removal of an object
 */
class ConfirmRemovalForm extends Form
{
    /**
     * Initalize this form
     */
    public function init()
    {
        $this->setName('form_confirm_removal');
        $this->setSubmitLabel($this->translate('Confirm Removal'));
    }
}
