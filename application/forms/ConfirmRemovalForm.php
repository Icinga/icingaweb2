<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form;

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
        $this->setSubmitLabel(t('Confirm Removal'));
    }
}
