<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Config\Instance;

use Icinga\Web\Form;

class LocalInstanceForm extends Form
{
    /**
     * (non-PHPDoc)
     * @see Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setName('form_config_monitoring_instance_local');
    }

    /**
     * (non-PHPDoc)
     * @see Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $this->addElement(
            'text',
            'path',
            array(
                'required'      => true,
                'label'         => $this->translate('Command File'),
                'value'         => '/var/run/icinga2/cmd/icinga2.cmd',
                'description'   => $this->translate('Path to the local Icinga command file')
            )
        );
        return $this;
    }
}
