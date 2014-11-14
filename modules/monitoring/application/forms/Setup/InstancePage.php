<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Setup;

use Icinga\Web\Form;
use Icinga\Module\Monitoring\Form\Config\InstanceConfigForm;

class InstancePage extends Form
{
    public function init()
    {
        $this->setName('setup_monitoring_instance');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'note',
            'title',
            array(
                'value'         => mt('monitoring', 'Monitoring Instance', 'setup.page.title'),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'h2'))
                )
            )
        );
        $this->addElement(
            'note',
            'description',
            array(
                'value' => mt(
                    'monitoring',
                    'Please define the settings specific to your monitoring instance below.'
                )
            )
        );

        if (isset($formData['host'])) {
            $formData['type'] = 'remote'; // This is necessary as the type element gets ignored by Form::getValues()
        }

        $instanceConfigForm = new InstanceConfigForm();
        $instanceConfigForm->createElements($formData);
        $this->addElements($instanceConfigForm->getElements());
        $this->getElement('name')->setValue('icinga');
    }
}
