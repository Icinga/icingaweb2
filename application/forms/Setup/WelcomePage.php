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
     * The configuration directory displayed to the user in the "generate security token" infobox
     *
     * @var string
     */
    public $configDir = '/etc/icingaweb';

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_welcome');
        $this->setViewScript('form/setup-welcome.phtml');
        $this->configDir = preg_replace('_/$_', '', Icinga::app()->getConfigDir());
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'token',
            array(
                'required'      => true,
                'label'         => t('Setup Token'),
                'description'   => t(
                    'For security reasons, we need to check that you are permitted to execute this setup. Please provide the token from the file "setup.token".'
                ),
                'validators'    => array(new TokenValidator(Icinga::app()->getConfigDir() . '/setup.token'))
            )
        );
    }
}
