<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Forms\Setup;

use Icinga\Web\Form;

class WelcomePage extends Form
{
    public function init()
    {
        $this->setName('setup_monitoring_welcome');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'note',
            'welcome',
            array(
                'value'         => $this->translate('Welcome to the configuration of the monitoring module for Icinga Web 2!'),
                'decorators'    => array(
                    'ViewHelper',
                    array('HtmlTag', array('tag' => 'h2'))
                )
            )
        );

        $this->addElement(
            'note',
            'core_hint',
            array(
                'value'         => $this->translate('This is the core module for Icinga Web 2.'),
                'decorators'    => array('ViewHelper')
            )
        );

        $this->addElement(
            'note',
            'description',
            array(
                'value'         => $this->translate(
                    'It offers various status and reporting views with powerful filter capabilities that allow'
                    . ' you to keep track of the most important events in your monitoring environment.'
                ),
                'decorators'    => array('ViewHelper')
            )
        );

        $this->addDisplayGroup(
            array('core_hint', 'description'),
            'info',
            array(
                'decorators' => array(
                    'FormElements',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'info'))
                )
            )
        );
    }
}
