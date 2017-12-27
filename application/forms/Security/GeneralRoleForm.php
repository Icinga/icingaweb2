<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Security;

use Icinga\Forms\ConfigForm;

class GeneralRoleForm extends ConfigForm
{
    /**
     * {@inheritdoc}
     */
    public function init()
    {
        $this->setName('form_general_role');
    }

    /**
     * {@inheritdoc}
     */
    public function createElements(array $formData = array())
    {
        $this->addElements(array(
            array(
                'text',
                'name',
                array(
                    'required' => true,
                    'label' => $this->translate('Role Name'),
                    'description' => $this->translate('The name of the role'),
                    'ignore' => true
                ),
            ),
            array(
                'textarea',
                'users',
                array(
                    'label' => $this->translate('Users'),
                    'description' => $this->translate('Comma-separated list of users that are assigned to the role')
                ),
            ),
            array(
                'textarea',
                'groups',
                array(
                    'label' => $this->translate('Groups'),
                    'description' => $this->translate('Comma-separated list of groups that are assigned to the role')
                ),
            ),

        ));
    }
}
