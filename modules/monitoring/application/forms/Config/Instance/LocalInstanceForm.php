<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Config\Instance;

use Icinga\Web\Form;

class LocalInstanceForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_monitoring_instance_local');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'path',
            array(
                'required'      => true,
                'label'         => t('Local Filepath'),
                'value'         => '/usr/local/icinga/var/rw/icinga.cmd',
                'description'   => t('The file path where the icinga commandpipe can be found')
            )
        );

        return $this;
    }
}
