<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config\Instance;

use Icinga\Web\Form;
use Icinga\Web\Form\Element\Number;

class RemoteInstanceForm extends Form
{
    public function init()
    {
        $this->setName('form_config_monitoring_instance_remote');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'host',
            array(
                'required'      => true,
                'label'         => t('Remote Host'),
                'description'   => t(
                    'Enter the hostname or address of the machine on which the icinga instance is running'
                )
            )
        );
        $this->addElement(
            new Number(
                array(
                    'required'      => true,
                    'name'          => 'port',
                    'label'         => t('Remote SSH Port'),
                    'description'   => t('Enter the ssh port to use for connecting to the remote icinga instance'),
                    'value'         => 22
                )
            )
        );
        $this->addElement(
            'text',
            'user',
            array(
                'required'      => true,
                'label'         => t('Remote SSH User'),
                'description'   => t(
                    'Enter the username to use for connecting to the remote machine or leave blank for default'
                )
            )
        );
        $this->addElement(
            'text',
            'path',
            array(
                'required'      => true,
                'label'         => t('Remote Filepath'),
                'value'         => '/usr/local/icinga/var/rw/icinga.cmd',
                'description'   => t('The file path where the icinga commandpipe can be found')
            )
        );

        return $this;
    }
}
