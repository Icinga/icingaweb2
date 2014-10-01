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
                    'value' => t('%WELCOME%')
                )
            )
        );
        $this->addElement(
            new Note(
                'description',
                array(
                    'value' => t('%DESCRIPTION%')
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
