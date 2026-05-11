<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

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
                'autofocus'     => '',
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
