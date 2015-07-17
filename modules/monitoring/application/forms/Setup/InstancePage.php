<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Setup;

use Icinga\Web\Form;
use Icinga\Module\Monitoring\Forms\Config\InstanceConfigForm;

class InstancePage extends Form
{
    public function init()
    {
        $this->setName('setup_monitoring_instance');
        $this->setTitle($this->translate('Monitoring Instance', 'setup.page.title'));
        $this->addDescription($this->translate(
            'Please define the settings specific to your monitoring instance below.'
        ));
    }

    public function createElements(array $formData)
    {
        $instanceConfigForm = new InstanceConfigForm();
        $this->addSubForm($instanceConfigForm, 'instance_form');
        $instanceConfigForm->create($formData);
        $instanceConfigForm->getElement('name')->setValue('icinga');
    }

    public function getValues($suppressArrayNotation = false)
    {
        return $this->getSubForm('instance_form')->getValues($suppressArrayNotation);
    }
}
