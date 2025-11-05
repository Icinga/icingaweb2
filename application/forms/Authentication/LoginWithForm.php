<?php
/* Icinga Web 2 | (c) 2025 Icinga GmbH | GPLv2+ */

namespace Icinga\forms\Authentication;

use Icinga\Web\Session;
use ipl\Html\Form;
use ipl\Html\FormElement\SubmitButtonElement;
use ipl\Web\Common\CsrfCounterMeasure;

/**
 * Form for user authentication via external identity providers
 */
class LoginWithForm extends Form
{
    use CsrfCounterMeasure;

    protected function assemble(): void
    {
        $this->addElement(new SubmitButtonElement('btn_submit'));
        $this->addElement($this->createCsrfCounterMeasure(Session::getSession()->getId()));
    }
}
