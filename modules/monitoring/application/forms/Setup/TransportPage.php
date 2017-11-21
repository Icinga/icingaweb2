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
        $this->setValidatePartial(true);
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

    /**
     * Run the configured backend's inspection checks and show the result, if necessary
     *
     * This will only run any validation if the user pushed the 'transport_validation' button.
     *
     * @param   array   $formData
     *
     * @return  bool
     */
    public function isValidPartial(array $formData)
    {
        if (isset($formData['transport_validation']) && parent::isValid($formData)) {
            $this->info($this->translate('The configuration has been successfully validated.'));
        } elseif (! isset($formData['transport_validation'])) {
            // This is usually done by isValid(Partial), but as we're not calling any of these...
            $this->populate($formData);
        }

        return true;
    }
}
