<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Config\Instance;

use Icinga\Web\Form;

class RemoteInstanceForm extends Form
{
    /**
     * (non-PHPDoc)
     * @see Form::init() For the method documentation.
     */
    public function init()
    {
        $this->setName('form_config_monitoring_instance_remote');
    }

    /**
     * (non-PHPDoc)
     * @see Form::createElements() For the method documentation.
     */
    public function createElements(array $formData = array())
    {
        $this->addElements(array(
            array(
                'text',
                'host',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Host'),
                    'description'   => $this->translate(
                        'Hostname or address of the remote Icinga instance'
                    )
                )
            ),
            array(
                'number',
                'port',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Port'),
                    'description'   => $this->translate('SSH port to connect to on the remote Icinga instance'),
                    'value'         => 22
                )
            ),
            array(
                'text',
                'user',
                array(
                    'required'      => true,
                    'label'         => $this->translate('User'),
                    'description'   => $this->translate(
                        'User to log in as on the remote Icinga instance. Please note that key-based SSH login must be'
                        . ' possible for this user'
                    )
                )
            ),
            array(
                'text',
                'path',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Command File'),
                    'value'         => '/var/run/icinga2/cmd/icinga2.cmd',
                    'description'   => $this->translate('Path to the Icinga command file on the remote Icinga instance')
                )
            )
        ));
        return $this;
    }
}
