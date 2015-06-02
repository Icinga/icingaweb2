<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\UserGroup;

use Icinga\Data\ResourceFactory;
use Icinga\Web\Form;

/**
 * Form for managing database user group backends
 */
class DbUserGroupBackendForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_dbusergroupbackend');
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData
     */
    public function createElements(array $formData)
    {
        $resourceNames = $this->getDatabaseResourceNames();
        $this->addElement(
            'select',
            'resource',
            array(
                'required'      => true,
                'label'         => $this->translate('Database Connection'),
                'description'   => $this->translate('The database connection to use for this backend'),
                'multiOptions'  => empty($resourceNames) ? array() : array_combine($resourceNames, $resourceNames)
            )
        );
    }

    /**
     * Return the names of all configured database resources
     *
     * @return  array
     */
    protected function getDatabaseResourceNames()
    {
        $names = array();
        foreach (ResourceFactory::getResourceConfigs() as $name => $config) {
            if (strtolower($config->type) === 'db') {
                $names[] = $name;
            }
        }

        return $names;
    }
}
