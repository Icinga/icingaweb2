<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Setup;

use Icinga\Web\Form;
use Icinga\Module\Monitoring\Forms\Config\InstanceConfigForm;

class InstancePage extends Form
{
    public function init()
    {
        $this->setName('setup_command_transport');
        $this->setTitle($this->translate('Command Transport', 'setup.page.title'));
        $this->addDescription($this->translate(
            'Please define below how you want to send commands to your monitoring instance.'
        ));
    }

    public function createElements(array $formData)
    {
        $transportConfigForm = new InstanceConfigForm();
        $this->addSubForm($transportConfigForm, 'transport_form');
        $transportConfigForm->create($formData);
        $transportConfigForm->getElement('name')->setValue('icinga2');
    }

    public function getValues($suppressArrayNotation = false)
    {
        return $this->getSubForm('transport_form')->getValues($suppressArrayNotation);
    }
}
