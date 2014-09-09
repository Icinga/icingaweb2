<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form\Config\Resource;

use Exception;
use Zend_Config;
use Icinga\Web\Form;
use Icinga\Web\Request;
use Icinga\Web\Form\Element\Number;
use Icinga\Data\ResourceFactory;

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
        $this->addElement(
            'select',
            'db',
            array(
                'required'      => true,
                'label'         => t('Database Type'),
                'description'   => t('The type of SQL database'),
                'multiOptions'  => array(
                    'mysql'     => 'MySQL',
                    'pgsql'     => 'PostgreSQL'
                    //'oracle'    => 'Oracle'
                )
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
            new Number(
                array(
                    'required'      => true,
                    'name'          => 'port',
                    'label'         => t('Port'),
                    'description'   => t('The port to use'),
                    'value'         => 3306
                )
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
    public function onSuccess(Request $request)
    {
        if (false === $this->isValidResource($this)) {
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
    public function isValidResource(Form $form)
    {
        try {
            $resource = ResourceFactory::createResource(new Zend_Config($form->getValues()));
            $resource->getConnection()->getConnection();
        } catch (Exception $e) {
            $form->addError(t('Connectivity validation failed, connection to the given resource not possible.'));
            return false;
        }

        return true;
    }
}
