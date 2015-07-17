<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\UserBackend;

use Exception;
use Icinga\Web\Form;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Authentication\User\DbUserBackend;

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
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $this->addElement(
            'text',
            'name',
            array(
                'required'      => true,
                'label'         => $this->translate('Backend Name'),
                'description'   => $this->translate(
                    'The name of this authentication provider that is used to differentiate it from others'
                ),
            )
        );
        $this->addElement(
            'select',
            'resource',
            array(
                'required'      => true,
                'label'         => $this->translate('Database Connection'),
                'description'   => $this->translate(
                    'The database connection to use for authenticating with this provider'
                ),
                'multiOptions'  => false === empty($this->resources)
                    ? array_combine($this->resources, $this->resources)
                    : array()
            )
        );
        $this->addElement(
            'hidden',
            'backend',
            array(
                'disabled'  => true,
                'value'     => 'db'
            )
        );

        return $this;
    }

    /**
     * Validate that the selected resource is a valid database user backend
     *
     * @see Form::onSuccess()
     */
    public function onSuccess()
    {
        if (false === static::isValidUserBackend($this)) {
            return false;
        }
    }

    /**
     * Validate the configuration by creating a backend and requesting the user count
     *
     * @param   Form    $form   The form to fetch the configuration values from
     *
     * @return  bool            Whether validation succeeded or not
     */
    public static function isValidUserBackend(Form $form)
    {
        $backend = new DbUserBackend(ResourceFactory::createResource($form->getResourceConfig()));
        $result = $backend->inspect();
        if ($result->hasError()) {
            $form->addError(sprintf($form->translate('Using the specified backend failed: %s'), $result->getError()));
        }

        // TODO: display diagnostics in $result->toArray() to the user

        return ! $result->hasError();
    }

    /**
     * Return the configuration for the chosen resource
     *
     * @return  ConfigObject
     */
    public function getResourceConfig()
    {
        return ResourceFactory::getResourceConfig($this->getValue('resource'));
    }
}
