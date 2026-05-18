<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Module\Setup\Forms;

use Icinga\Authentication\User\ExternalBackend;
use Icinga\Web\Form;
use Icinga\Application\Platform;

/**
 * Wizard page to choose an authentication backend
 */
class AuthenticationPage extends Form
{
    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setRequiredCue(null);
        $this->setName('setup_authentication_type');
        $this->setTitle($this->translate('Authentication', 'setup.page.title'));
        $this->addDescription($this->translate(
            'Please choose how you want to authenticate when accessing Icinga Web 2.'
            . ' Configuring backend specific details follows in a later step.'
        ));
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        if (isset($formData['type']) && $formData['type'] === 'external') {
            list($username, $_) = ExternalBackend::getRemoteUserInformation();
            if ($username === null) {
                $this->info(
                    $this->translate(
                        'You\'re currently not authenticated using any of the web server\'s authentication '
                        . 'mechanisms. Make sure you\'ll configure such, otherwise you\'ll not be able to '
                        . 'log into Icinga Web 2.'
                    ),
                    false
                );
            }
        }

        $backendTypes = [];
        if (Platform::hasMysqlSupport() || Platform::hasPostgresqlSupport()) {
            $backendTypes['db'] = $this->translate('Database');
        }
        if (Platform::extensionLoaded('ldap')) {
            $backendTypes['ldap'] = 'LDAP';
        }
        $backendTypes['external'] = $this->translate('External');

        $this->addElement(
            'select',
            'type',
            [
                'required'      => true,
                'autosubmit'    => true,
                'label'         => $this->translate('Authentication Type'),
                'description'   => $this->translate('The type of authentication to use when accessing Icinga Web 2'),
                'multiOptions'  => $backendTypes
            ]
        );
    }
}
