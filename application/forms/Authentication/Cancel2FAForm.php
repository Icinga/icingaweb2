<?php

namespace Icinga\Forms\Authentication;

use Icinga\Web\Form;
use Icinga\Web\Session;
use Icinga\Web\Url;

class Cancel2FAForm extends Form
{
    public function init()
    {
        $this->setRequiredCue(null);
        $this->setName('form_cancel_2fa');
        $this->setAttrib('class', 'content-centered');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'hidden',
            'redirect',
            [
                'value' => Url::fromRequest()->getParam('redirect')
            ]
        );

        $this->addElement(
            'hidden',
            'cancel_2fa',
            [
                'value' => true
            ]
        );

        $this->addElement(
            'submit',
            'btn_submit',
            [
                'class'                 => 'btn-cancel',
                'ignore'                => true,
                'label'                 => 'Cancel',
                'data-progress-label'   => 'Canceling',
                'decorators'            => [
                    'ViewHelper',
                    ['Spinner', ['separator' => '']],
                    ['HtmlTag', ['tag' => 'div', 'class' => 'control-group form-controls']]
                ]
            ]
        );
    }

    public function onSuccess()
    {
        Session::getSession()->purge();

        return true;
    }
}
