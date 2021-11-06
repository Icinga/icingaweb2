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
                    'required'          => true,
                    'preserveDefault'   => true,
                    'label'             => $this->translate('Port'),
                    'description'       => $this->translate('SSH port to connect to on the remote Icinga instance'),
                    'value'             => 5665
                )
            ),
            array(
                'text',
                'username',
                array(
                    'required'      => false,
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
                    'required'      => false,
                    'label'         => $this->translate('API Password'),
                    'description'   => $this->translate(
                        'User to log in as on the remote Icinga instance. Please note that key-based SSH login must be'
                        . ' possible for this user'
                    ),
                    'renderPassword'    => true
                )
            ),
            array(
                'text',
                'caFile',
                array(
                    'required'      => false,
                    'label'         => $this->translate('CA Certificate'),
                    'description'   => $this->translate('Path to a CA certificate to verify the API host with. Leave unset to disable verification')
                )
            ),
            array(
                'checkbox',
                'verifyHostname',
                array(
                    'required'      => false,
                    'label'         => $this->translate('Verify hostname'),
                    'description'   => $this->translate('Whether to verify the hostname in the certificate when CA verification is enabled')
                )
            ),
            array(
                'text',
                'clientKey',
                array(
                    'required'      => false,
                    'label'         => $this->translate('Client Key'),
                    'description'   => $this->translate('Path to a private key for client authentication')
                )
            ),
            array(
                'text',
                'clientCert',
                array(
                    'required'      => false,
                    'label'         => $this->translate('Client Certificate'),
                    'description'   => $this->translate('Path to a public certificate for client authentication')
                )
            )
        ));

        return $this;
    }
}
