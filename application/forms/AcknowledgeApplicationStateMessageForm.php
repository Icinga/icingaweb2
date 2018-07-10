<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use Icinga\Application\Hook\ApplicationStateHook;
use Icinga\Web\ApplicationStateCookie;
use Icinga\Web\Form;
use Icinga\Web\Url;

class AcknowledgeApplicationStateMessageForm extends Form
{
    public function init()
    {
        $this->setAction(Url::fromPath('application-state/acknowledge-message'));
        $this->setAttrib('class', 'form-inline application-state-acknowledge-message-control');
        $this->setRedirectUrl('application-state/summary');
    }

    public function addSubmitButton()
    {
        $this->addElement(
            'button',
            'btn_submit',
            [
                'class'         => 'link-button spinner',
                'decorators'    => [
                    'ViewHelper',
                    ['HtmlTag', ['tag' => 'div', 'class' => 'control-group form-controls']]
                ],
                'escape'        => false,
                'ignore'        => true,
                'label'         => $this->getView()->icon('cancel'),
                'title'         => $this->translate('Acknowledge message'),
                'type'          => 'submit'
            ]
        );
        return $this;
    }

    public function createElements(array $formData = [])
    {
        $this->addElements(
            [
                [
                    'hidden',
                    'id',
                    [
                        'required' => true,
                        'validators' => ['NotEmpty'],
                        'decorators' => ['ViewHelper']
                    ]
                ]
            ]
        );

        return $this;
    }

    public function onSuccess()
    {
        $cookie = new ApplicationStateCookie();

        $ack = $cookie->getAcknowledgedMessages();
        $ack[] = $this->getValue('id');

        $active = ApplicationStateHook::getAllMessages();

        $cookie->setAcknowledgedMessages(array_keys(array_intersect_key($active, array_flip($ack))));

        $this->getResponse()->setCookie($cookie);

        return true;
    }
}
