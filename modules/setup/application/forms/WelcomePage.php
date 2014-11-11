<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup\Form;

use Icinga\Application\Icinga;
use Icinga\Web\Form;
use Icinga\Module\Setup\Web\Form\Validator\TokenValidator;

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
        $this->setViewScript('form/setup-welcome.phtml');
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
                'label'         => mt('setup', 'Setup Token'),
                'description'   => mt(
                    'setup',
                    'For security reasons we need to ensure that you are permitted to run this wizard.'
                    . ' Please provide a token by following the instructions below.'
                ),
                'validators'    => array(new TokenValidator(Icinga::app()->getConfigDir() . '/setup.token'))
            )
        );
    }
}
