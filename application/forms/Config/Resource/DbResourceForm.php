<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
                'label'         => t('Resource Name'),
                'description'   => t('The unique name of this resource')
            )
        );
        $this->addElement(
            'select',
            'db',
            array(
                'required'      => true,
                'label'         => t('Database Type'),
                'description'   => t('The type of SQL database'),
                'multiOptions'  => $dbChoices
            )
        );
        $this->addElement(
            'text',
            'host',
            array (
                'required'      => true,
                'label'         => t('Host'),
                'description'   => t('The hostname of the database'),
                'value'         => 'localhost'
            )
        );
        $this->addElement(
            'number',
            'port',
            array(
                'required'      => true,
                'label'         => t('Port'),
                'description'   => t('The port to use'),
                'value'         => 3306
            )
        );
        $this->addElement(
            'text',
            'dbname',
            array(
                'required'      => true,
                'label'         => t('Database Name'),
                'description'   => t('The name of the database to use')
            )
        );
        $this->addElement(
            'text',
            'username',
            array (
                'required'      => true,
                'label'         => t('Username'),
                'description'   => t('The user name to use for authentication')
            )
        );
        $this->addElement(
            'password',
            'password',
            array(
                'required'          => true,
                'renderPassword'    => true,
                'label'             => t('Password'),
                'description'       => t('The password to use for authentication')
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
            $form->addError(t('Connectivity validation failed, connection to the given resource not possible.'));
            return false;
        }

        return true;
    }
}
