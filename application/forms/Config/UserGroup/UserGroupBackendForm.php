<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms\Config\UserGroup;

use InvalidArgumentException;
use Icinga\Exception\IcingaException;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ConfigForm;

/**
 * Form for managing user group backends
 */
class UserGroupBackendForm extends ConfigForm
{
    /**
     * The backend to load when displaying the form for the first time
     *
     * @var string
     */
    protected $backendToLoad;

    /**
     * Initialize this form
     */
    public function init()
    {
        $this->setName('form_config_usergroupbackend');
        $this->setSubmitLabel($this->translate('Save Changes'));
    }

    /**
     * Return a form object for the given backend type
     *
     * @param   string  $type               The backend type for which to return a form
     *
     * @return  Form
     *
     * @throws  InvalidArgumentException    In case the given backend type is invalid
     */
    public function getBackendForm($type)
    {
        switch ($type) {
            case 'db':
                return new DbUserGroupBackendForm();
            case 'ldap':
            case 'msldap':
                return new LdapUserGroupBackendForm();
            default:
                throw new InvalidArgumentException(
                    sprintf($this->translate('Invalid backend type "%s" provided'), $type)
                );
        }
    }

    /**
     * Populate the form with the given backend's config
     *
     * @param   string  $name
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no backend with the given name is found
     */
    public function load($name)
    {
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError('No user group backend called "%s" found', $name);
        }

        $this->backendToLoad = $name;
        return $this;
    }

    /**
     * Add a new user group backend
     *
     * The backend to add is identified by the array-key `name'.
     *
     * @param   array   $data
     *
     * @return  $this
     *
     * @throws  InvalidArgumentException    In case $data does not contain a backend name
     * @throws  IcingaException             In case a backend with the same name already exists
     */
    public function add(array $data)
    {
        if (! isset($data['name'])) {
            throw new InvalidArgumentException('Key \'name\' missing');
        }

        $backendName = $data['name'];
        if ($this->config->hasSection($backendName)) {
            throw new IcingaException('A user group backend with the name "%s" does already exist', $backendName);
        }

        unset($data['name']);
        $this->config->setSection($backendName, $data);
        return $this;
    }

    /**
     * Edit a user group backend
     *
     * @param   string  $name
     * @param   array   $data
     *
     * @return  $this
     *
     * @throws  NotFoundError   In case no backend with the given name is found
     */
    public function edit($name, array $data)
    {
        if (! $this->config->hasSection($name)) {
            throw new NotFoundError('No user group backend called "%s" found', $name);
        }

        $backendConfig = $this->config->getSection($name);
        if (isset($data['name'])) {
            if ($data['name'] !== $name) {
                $this->config->removeSection($name);
                $name = $data['name'];
            }

            unset($data['name']);
        }

        $this->config->setSection($name, $backendConfig->merge($data));
        return $this;
    }

    /**
     * Remove a user group backend
     *
     * @param   string  $name
     *
     * @return  $this
     */
    public function delete($name)
    {
        $this->config->removeSection($name);
        return $this;
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData
     */
    public function createElements(array $formData)
    {
        // TODO(jom): We did not think about how to configure custom group backends yet!
        $backendTypes = array(
            'db'        => $this->translate('Database'),
            'ldap'      => 'LDAP',
            'msldap'    => 'ActiveDirectory'
        );

        $backendType = isset($formData['type']) ? $formData['type'] : null;
        if ($backendType === null) {
            $backendType = key($backendTypes);
        }

        $this->addElement(
            'select',
            'type',
            array(
                'ignore'            => true,
                'required'          => true,
                'autosubmit'        => true,
                'label'             => $this->translate('Backend Type'),
                'description'       => $this->translate('The type of this user group backend'),
                'multiOptions'      => $backendTypes
            )
        );

        $this->addSubForm($this->getBackendForm($backendType)->create($formData), 'backend_form');
    }

    /**
     * Populate the configuration of the backend to load
     */
    public function onRequest()
    {
        if ($this->backendToLoad) {
            $data = $this->config->getSection($this->backendToLoad)->toArray();
            $data['type'] = $data['backend'];
            $data['name'] = $this->backendToLoad;
            $this->populate($data);
        }
    }
}
