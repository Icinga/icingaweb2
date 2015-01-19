<?php

namespace Icinga\Module\Monitoring\Forms\Config\Instance;

use Icinga\Web\Form;
use Icinga\Web\Wizard;
use Icinga\Web\WizardPage;

class RemoteInstanceKeyResourcePage extends Form implements WizardPage
{
    public function onRequest()
    {
        
    }

    public function onSuccess()
    {
        
    }

    public function createElements(array $formData)
    {
        
    }

    public function setup(Wizard $wizard)
    {
        
    }

    public function isRequired(Wizard $wizard)
    {
        return true;
    }

    public function addNextButton($pageName)
    {
        $this->addElement(
            'button',
            Wizard::BTN_NEXT,
            array(
                'type'          => 'submit',
                'value'         => $pageName,
                'label'         => $this->translate('Save Changes'),
                'decorators'    => array('ViewHelper')
            )
        );
    }

    public function addPreviousButton($pageName)
    {
        
    }
}
