<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Monitoring\Form\Setup;

use Icinga\Web\Form;
use Icinga\Web\Form\Element\Note;

class WelcomePage extends Form
{
    public function init()
    {
        $this->setName('setup_monitoring_welcome');
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            new Note(
                'welcome',
                array(
                    'value' => mt(
                        'monitoring',
                        'Welcome to the configuration of the monitoring module for Icinga Web 2!'
                    ),
                    'decorators'    => array(
                        'ViewHelper',
                        array('HtmlTag', array('tag' => 'h2'))
                    )
                )
            )
        );

        $this->addElement(
            new Note(
                'core_hint',
                array(
                    'value' => mt('monitoring', 'This is the core module for Icinga Web 2.')
                )
            )
        );

        $this->addElement(
            new Note(
                'description',
                array(
                    'value' => mt(
                        'monitoring',
                        'It offers various status and reporting views with powerful filter capabilities that allow'
                        . ' you to keep track of the most important events in your monitoring environment.'
                    )
                )
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
