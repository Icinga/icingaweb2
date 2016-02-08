<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Setup;

use Icinga\Web\Form;
use Icinga\Module\Monitoring\Forms\Config\SecurityConfigForm;

class SecurityPage extends Form
{
    public function init()
    {
        $this->setName('setup_monitoring_security');
        $this->setTitle($this->translate('Monitoring Security', 'setup.page.title'));
        $this->addDescription($this->translate(
            'To protect your monitoring environment against prying eyes please fill out the settings below.'
        ));
    }

    public function createElements(array $formData)
    {
        $securityConfigForm = new SecurityConfigForm();
        $securityConfigForm->createElements($formData);
        $this->addElements($securityConfigForm->getElements());
    }
}
