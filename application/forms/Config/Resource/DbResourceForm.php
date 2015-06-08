<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\Resource;

use Exception;
use Icinga\Web\Form;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Application\Platform;

/**
 * Form class for adding/modifying database resources
 */
class DbResourceForm extends Form
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_resource_db');
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $dbChoices = array();
        if (Platform::hasMysqlSupport()) {
            $dbChoices['mysql'] = 'MySQL';
        }
        if (Platform::hasPostgresqlSupport()) {
            $dbChoices['pgsql'] = 'PostgreSQL';
        }

        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Resource Name'),
                'description'   => $this->translate('The unique name of this resource')
            )
        );
        $this->addElement(
            'select',
            'db',
            array(
                'required'      => true,
                'autosubmit'    => true,
                'label'         => $this->translate('Database Type'),
                'description'   => $this->translate('The type of SQL database'),
                'multiOptions'  => $dbChoices
            )
        );
        $this->addElement(
            'text',
            'host',
            array (
                'required'      => true,
                'label'         => $this->translate('Host'),
                'description'   => $this->translate('The hostname of the database'),
                'value'         => 'localhost'
            )
        );
        $this->addElement(
            'number',
            'port',
            array(
                'required'          => true,
                'preserveDefault'   => true,
                'label'             => $this->translate('Port'),
                'description'       => $this->translate('The port to use'),
                'value'             => ! array_key_exists('db', $formData) || $formData['db'] === 'mysql' ? 3306 : 5432
            )
        );
        $this->addElement(
            'text',
            'dbname',
            array(
                'required'      => true,
                'label'         => $this->translate('Database Name'),
                'description'   => $this->translate('The name of the database to use')
            )
        );
        $this->addElement(
            'text',
            'username',
            array (
                'required'      => true,
                'label'         => $this->translate('Username'),
                'description'   => $this->translate('The user name to use for authentication')
            )
        );
        $this->addElement(
            'password',
            'password',
            array(
                'required'          => true,
                'renderPassword'    => true,
                'label'             => $this->translate('Password'),
                'description'       => $this->translate('The password to use for authentication')
            )
        );

        return $this;
    }

    /**
     * Validate that the current configuration points to a valid resource
     *
     * @see Form::onSuccess()
     */
    public function onSuccess()
    {
        if (false === static::isValidResource($this)) {
            return false;
        }
    }

    /**
     * Validate the resource configuration by trying to connect with it
     *
     * @param   Form    $form   The form to fetch the configuration values from
     *
     * @return  bool            Whether validation succeeded or not
     */
    public static function isValidResource(Form $form)
    {
        try {
            $resource = ResourceFactory::createResource(new ConfigObject($form->getValues()));
            $resource->getConnection()->getConnection();
        } catch (Exception $e) {
            $form->addError(
                $form->translate('Connectivity validation failed, connection to the given resource not possible.')
            );
            return false;
        }

        return true;
    }
}
