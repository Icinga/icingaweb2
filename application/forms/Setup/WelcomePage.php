<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Setup;

use Icinga\Application\Icinga;
use Icinga\Web\Form;
use Icinga\Web\Form\Element\Note;
use Icinga\Web\Form\Validator\TokenValidator;

/**
 * Wizard page to authenticate and welcome the user
 */
class WelcomePage extends Form
{
    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_welcome');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            new Note(
                'welcome',
                array(
                    'value'         => t('Welcome to the installation of Icinga Web 2!'),
                    'decorators'    => array(
                        'ViewHelper',
                        array('HtmlTag', array('tag' => 'h2'))
                    )
                )
            )
        );
        $this->addElement( // TODO: Remove this once we release the first public version
            new Note(
                'wip',
                array(
                    'value'         => 'Icinga Web 2 is still in development and not meant for production deployment.'
                    . ' Watch the <a href="https://dev.icinga.org/projects/icingaweb2/roadmap">development roadmap</a>'
                    . ' and <a href="https://www.icinga.org/">Icinga website</a> for release schedule updates!',
                    'decorators'    => array(
                        'ViewHelper',
                        array(
                            'HtmlTag',
                            array(
                                'tag'   => 'div',
                                'style' => 'border:1px solid #777;border-radius:1em;background-color:beige;'
                                . 'padding:1em;margin-bottom:1em;display:inline-block;'
                            )
                        )
                    )
                )
            )
        );
        $this->addElement(
            new Note(
                'description',
                array(
                    'value' => sprintf(
                        t(
                            'Icinga Web 2 is the next generation monitoring web interface,'
                            . ' framework and CLI tool developed by the %s.'
                        ),
                        '<a href="https://www.icinga.org/community/team/">' . t('Icinga Project') . '</a>'
                    )
                )
            )
        );
        $this->addElement(
            new Note(
                'facts',
                array(
                    'value' => t(
                        'Responsive and fast, rewritten from scratch supporting multiple backends and'
                        . ' providing a CLI tool. Compatible with Icinga Core 2.x and 1.x.'
                    )
                )
            )
        );
        $this->addElement(
            new Note(
                'insights',
                array(
                    'value' => sprintf(
                        t('Check the Icinga website for some %s.', 'setup.welcome.screenshots'),
                        '<a href="https://www.icinga.org/icinga/screenshots/icinga-web-2/">'
                        . t('insights', 'setup.welcome.screenshots.label') . '</a>'
                    )
                )
            )
        );
        $this->addDisplayGroup(
            array('description', 'facts', 'insights'),
            'info',
            array(
                'decorators' => array(
                    'FormElements',
                    array('HtmlTag', array('tag' => 'div', 'class' => 'info'))
                )
            )
        );
        $this->addElement(
            new Note(
                'purpose',
                array(
                    'value' => t(
                        'This wizard will guide you through the installation of Icinga Web 2.'
                        . ' Once completed and successfully finished you are able to log in '
                        . 'and to explore all the new and stunning features!'
                    )
                )
            )
        );
        $this->addElement(
            'text',
            'token',
            array(
                'required'      => true,
                'label'         => t('Setup Token'),
                'description'   => t('Please enter the setup token you\'ve created earlier by using the icingacli'),
                'validators'    => array(new TokenValidator(Icinga::app()->getConfigDir() . '/setup.token'))
            )
        );
    }
}
