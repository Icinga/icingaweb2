<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Forms\Config\UserBackend;

use Icinga\Web\Form;

/**
 * Form class for adding/modifying database user backends
 */
class DbBackendForm extends Form
{
    /**
     * The database resource names the user can choose from
     *
     * @var array
     */
    protected $resources;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_authbackend_db');
    }

    /**
     * Set the resource names the user can choose from
     *
     * @param   array   $resources      The resources to choose from
     *
     * @return  $this
     */
    public function setResources(array $resources)
    {
        $this->resources = $resources;
        return $this;
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            [
                'required'      => true,
                'label'         => $this->translate('Backend Name'),
                'description'   => $this->translate(
                    'The name of this authentication provider that is used to differentiate it from others'
                )
            ]
        );
        $this->addElement(
            'select',
            'resource',
            [
                'required'      => true,
                'label'         => $this->translate('Database Connection'),
                'description'   => $this->translate(
                    'The database connection to use for authenticating with this provider'
                ),
                'multiOptions'  => !empty($this->resources)
                    ? array_combine($this->resources, $this->resources)
                    : []
            ]
        );
        $this->addElement(
            'hidden',
            'backend',
            [
                'disabled'  => true,
                'value'     => 'db'
            ]
        );
    }
}
