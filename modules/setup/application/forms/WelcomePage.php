<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

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
        $this->setRequiredCue(null);
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
                'class'         => 'autofocus',
                'required'      => true,
                'label'         => $this->translate('Setup Token'),
                'description'   => $this->translate(
                    'For security reasons we need to ensure that you are permitted to run this wizard.'
                    . ' Please provide a token by following the instructions below.'
                ),
                'validators'    => array(new TokenValidator(Icinga::app()->getConfigDir() . '/setup.token'))
            )
        );
    }
}
