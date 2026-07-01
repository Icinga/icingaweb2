<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms;

use Icinga\Web\Form;

class LdapDiscoveryForm extends Form
{
    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('form_ldap_discovery');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'domain',
            [
                'label'         => $this->translate('Search Domain'),
                'description'   => $this->translate('Search this domain for records of available servers.'),
            ]
        );

        return $this;
    }
}
