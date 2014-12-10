<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
                    'label'         => mt('monitoring', 'Host'),
                    'description'   => mt('monitoring',
                        'Hostname or address of the remote Icinga instance'
                    )
                )
            ),
            array(
                'number',
                'port',
                array(
                    'required'      => true,
                    'label'         => mt('monitoring', 'Port'),
                    'description'   => mt('monitoring', 'SSH port to connect to on the remote Icinga instance'),
                    'value'         => 22
                )
            ),
            array(
                'text',
                'user',
                array(
                    'required'      => true,
                    'label'         => mt('monitoring', 'User'),
                    'description'   => mt('monitoring',
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
                    'label'         => mt('monitoring', 'Command File'),
                    'value'         => '/var/run/icinga2/cmd/icinga2.cmd',
                    'description'   => mt('monitoring', 'Path to the Icinga command file on the remote Icinga instance')
                )
            )
        ));
        return $this;
    }
}
