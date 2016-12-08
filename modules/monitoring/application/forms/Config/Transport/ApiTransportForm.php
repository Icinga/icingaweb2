<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Config\Transport;

use Icinga\Web\Form;

class ApiTransportForm extends Form
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_config_command_transport_api');
    }

    /**
     * {@inheritdoc}
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
                    'value'         => 5665
                )
            ),
            array(
                'text',
                'username',
                array(
                    'required'      => true,
                    'label'         => $this->translate('API Username'),
                    'description'   => $this->translate(
                        'User to log in as on the remote Icinga instance. Please note that key-based SSH login must be'
                        . ' possible for this user'
                    )
                )
            ),
            array(
                'password',
                'password',
                array(
                    'required'      => true,
                    'label'         => $this->translate('API Password'),
                    'description'   => $this->translate(
                        'User to log in as on the remote Icinga instance. Please note that key-based SSH login must be'
                        . ' possible for this user'
                    )
                )
            )
        ));

        return $this;
    }
}
