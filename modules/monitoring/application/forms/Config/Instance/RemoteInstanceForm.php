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
                'label'         => mt('monitoring', 'Remote Host'),
                'description'   => mt('monitoring',
                    'Enter the hostname or address of the machine on which the icinga instance is running'
                )
            )
        );
        $this->addElement(
            new Number(
                array(
                    'required'      => true,
                    'name'          => 'port',
                    'label'         => mt('monitoring', 'Remote SSH Port'),
                    'description'   => mt('monitoring', 'Enter the ssh port to use for connecting to the remote icinga instance'),
                    'value'         => 22
                )
            )
        );
        $this->addElement(
            'text',
            'user',
            array(
                'required'      => true,
                'label'         => mt('monitoring', 'Remote SSH User'),
                'description'   => mt('monitoring',
                    'Enter the username to use for connecting to the remote machine or leave blank for default'
                )
            )
        );
        $this->addElement(
            'text',
            'path',
            array(
                'required'      => true,
                'label'         => mt('monitoring', 'Remote Filepath'),
                'value'         => '/usr/local/icinga/var/rw/icinga.cmd',
                'description'   => mt('monitoring', 'The file path where the icinga commandpipe can be found')
            )
        );

        return $this;
    }
}
