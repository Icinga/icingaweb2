<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config;

use InvalidArgumentException;
use Icinga\Web\Notification;
use Icinga\Forms\ConfigForm;
use Icinga\Forms\Config\Resource\DbResourceForm;
use Icinga\Forms\Config\Resource\FileResourceForm;
use Icinga\Forms\Config\Resource\LdapResourceForm;
use Icinga\Forms\Config\Resource\LivestatusResourceForm;
use Icinga\Application\Platform;
use Icinga\Exception\ConfigurationError;

class ResourceConfigForm extends ConfigForm
{
    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_resource');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    /**
     * Return a form object for the given resource type
     *
     * @param   string      $type      The resource type for which to return a form
     *
     * @return  Form
     */
    public function getResourceForm($type)
    {
        if ($type === 'db') {
            return new DbResourceForm();
        } elseif ($type === 'ldap') {
            return new LdapResourceForm();
        } elseif ($type === 'livestatus') {
            return new LivestatusResourceForm();
        } elseif ($type === 'file') {
            return new FileResourceForm();
        } else {
            throw new InvalidArgumentException(sprintf($this->translate('Invalid resource type "%s" provided'), $type));
        }
    }

    /**
     * Add a particular resource
     *
     * The backend to add is identified by the array-key `name'.
     *
     * @param   array   $values             The values to extend the configuration with
     *
     * @return  $this
     *
     * @thrwos  InvalidArgumentException    In case the resource does already exist
     */
    public function add(array $values)
    {
        $name = isset($values['name']) ? $values['name'] : '';
        if (! $name) {
            throw new InvalidArgumentException($this->translate('Resource name missing'));
        } elseif ($this->config->hasSection($name)) {
            throw new InvalidArgumentException($this->translate('Resource already exists'));
        }

        unset($values['name']);
        $this->config->setSection($name, $values);
        return $this;
    }

    /**
     * Edit a particular resource
     *
     * @param   string      $name           The name of the resource to edit
     * @param   array       $values         The values to edit the configuration with
     *
     * @return  array                       The edited configuration
     *
     * @throws  InvalidArgumentException    In case the resource does not exist
     */
    public function edit($name, array $values)
    {
        if (! $name) {
            throw new InvalidArgumentException($this->translate('Old resource name missing'));
        } elseif (! ($newName = isset($values['name']) ? $values['name'] : '')) {
            throw new InvalidArgumentException($this->translate('New resource name missing'));
        } elseif (! $this->config->hasSection($name)) {
            throw new InvalidArgumentException($this->translate('Unknown resource provided'));
        }

        $resourceConfig = $this->config->getSection($name);
        $this->config->removeSection($name);
        unset($values['name']);
        $this->config->setSection($newName, $resourceConfig->merge($values));
        return $resourceConfig;
    }

    /**
     * Remove a particular resource
     *
     * @param   string      $name           The name of the resource to remove
     *
     * @return  array                       The removed resource configuration
     *
     * @throws  InvalidArgumentException    In case the resource does not exist
     */
    public function remove($name)
    {
        if (! $name) {
            throw new InvalidArgumentException($this->translate('Resource name missing'));
        } elseif (! $this->config->hasSection($name)) {
            throw new InvalidArgumentException($this->translate('Unknown resource provided'));
        }

        $resourceConfig = $this->config->getSection($name);
        $this->config->removeSection($name);
        return $resourceConfig;
    }

    /**
     * Add or edit a resource and save the configuration
     *
     * Performs a connectivity validation using the submitted values. A checkbox is
     * added to the form to skip the check if it fails and redirection is aborted.
     *
     * @see Form::onSuccess()
     */
    public function onSuccess()
    {
        if (($el = $this->getElement('force_creation')) === null || false === $el->isChecked()) {
            $resourceForm = $this->getResourceForm($this->getElement('type')->getValue());
            if (method_exists($resourceForm, 'isValidResource') && false === $resourceForm::isValidResource($this)) {
                $this->addElement($this->getForceCreationCheckbox());
                return false;
            }
        }

        $resource = $this->request->getQuery('resource');
        try {
            if ($resource === null) { // create new resource
                $this->add($this->getValues());
                $message = $this->translate('Resource "%s" has been successfully created');
            } else { // edit existing resource
                $this->edit($resource, $this->getValues());
                $message = $this->translate('Resource "%s" has been successfully changed');
            }
        } catch (InvalidArgumentException $e) {
            Notification::error($e->getMessage());
            return;
        }

        if ($this->save()) {
            Notification::success(sprintf($message, $this->getElement('name')->getValue()));
        } else {
            return false;
        }
    }

    /**
     * Populate the form in case a resource is being edited
     *
     * @see Form::onRequest()
     *
     * @throws  ConfigurationError      In case the backend name is missing in the request or is invalid
     */
    public function onRequest()
    {
        $resource = $this->request->getQuery('resource');
        if ($resource !== null) {
            if ($resource === '') {
                throw new ConfigurationError($this->translate('Resource name missing'));
            } elseif (! $this->config->hasSection($resource)) {
                throw new ConfigurationError($this->translate('Unknown resource provided'));
            }

            $configValues = $this->config->getSection($resource)->toArray();
            $configValues['name'] = $resource;
            $this->populate($configValues);
        }
    }

    /**
     * Return a checkbox to be displayed at the beginning of the form
     * which allows the user to skip the connection validation
     *
     * @return  Zend_Form_Element
     */
    protected function getForceCreationCheckbox()
    {
        return $this->createElement(
            'checkbox',
            'force_creation',
            array(
                'order'         => 0,
                'ignore'        => true,
                'label'         => $this->translate('Force Changes'),
                'description'   => $this->translate('Check this box to enforce changes without connectivity validation')
            )
        );
    }

    /**
     * @see Form::createElemeents()
     */
    public function createElements(array $formData)
    {
        $resourceType = isset($formData['type']) ? $formData['type'] : 'db';

        $resourceTypes = array(
            'file'          => $this->translate('File'),
            'livestatus'    => 'Livestatus',
        );
        if ($resourceType === 'ldap' || Platform::extensionLoaded('ldap')) {
            $resourceTypes['ldap'] = 'LDAP';
        }
        if ($resourceType === 'db' || Platform::hasMysqlSupport() || Platform::hasPostgresqlSupport()) {
            $resourceTypes['db'] = $this->translate('SQL Database');
        }

        $this->addElement(
            'select',
            'type',
            array(
                'required'          => true,
                'autosubmit'        => true,
                'label'             => $this->translate('Resource Type'),
                'description'       => $this->translate('The type of resource'),
                'multiOptions'      => $resourceTypes,
                'value'             => $resourceType
            )
        );

        if (isset($formData['force_creation']) && $formData['force_creation']) {
            // In case another error occured and the checkbox was displayed before
            $this->addElement($this->getForceCreationCheckbox());
        }

        $this->addElements($this->getResourceForm($resourceType)->createElements($formData)->getElements());
    }
}
