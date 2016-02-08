<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Setup;

use Icinga\Web\Form;
use Icinga\Module\Monitoring\Forms\Config\TransportConfigForm;

class TransportPage extends Form
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
        $transportConfigForm = new TransportConfigForm();
        $this->addSubForm($transportConfigForm, 'transport_form');
        $transportConfigForm->create($formData);
        $transportConfigForm->removeElement('instance');
        $transportConfigForm->getElement('name')->setValue('icinga2');
    }

    public function getValues($suppressArrayNotation = false)
    {
        return $this->getSubForm('transport_form')->getValues($suppressArrayNotation);
    }
}
