<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config\Instance;

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
                'label'         => mt('monitoring', 'Command File'),
                'value'         => '/usr/local/icinga/var/rw/icinga.cmd',
                'description'   => mt('monitoring', 'Path to the local Icinga command file')
            )
        );
        return $this;
    }
}
