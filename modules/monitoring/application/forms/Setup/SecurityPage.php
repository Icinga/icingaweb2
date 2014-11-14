<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Setup;

use Icinga\Web\Form;
use Icinga\Module\Monitoring\Form\Config\SecurityConfigForm;

class SecurityPage extends Form
{
    public function init()
    {
        $this->setName('setup_monitoring_security');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'note',
            'title',
            array(
                'value'         => mt('monitoring', 'Monitoring Security', 'setup.page.title'),
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
                    'To protect your monitoring environment against prying eyes please fill out the settings below.'
                )
            )
        );

        $securityConfigForm = new SecurityConfigForm();
        $securityConfigForm->createElements($formData);
        $this->addElements($securityConfigForm->getElements());
    }
}
