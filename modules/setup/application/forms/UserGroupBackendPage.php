<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Icinga\Application\Config;
use Icinga\Authentication\User\UserBackend;
use Icinga\Data\ResourceFactory;
use Icinga\Forms\Config\UserGroup\LdapUserGroupBackendForm;
use Icinga\Web\Form;

/**
 * Wizard page to define user group backend specific details
 */
class UserGroupBackendPage extends Form
{
    /**
     * The resource configuration to use
     *
     * @var array
     */
    protected $resourceConfig;

    /**
     * The user backend configuration to use
     *
     * @var array
     */
    protected $backendConfig;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_usergroup_backend');
        $this->setTitle($this->translate('User Group Backend', 'setup.page.title'));
        $this->addDescription($this->translate(
            'To allow Icinga Web 2 to associate users and groups, you\'ll need to provide some further information'
            . ' about the LDAP Connection that is already going to be used to locate account details.'
        ));
    }

    /**
     * Set the resource configuration to use
     *
     * @param   array   $config
     *
     * @return  $this
     */
    public function setResourceConfig(array $config)
    {
        $this->resourceConfig = $config;
        return $this;
    }

    /**
     * Set the user backend configuration to use
     *
     * @param   array   $config
     *
     * @return  $this
     */
    public function setBackendConfig(array $config)
    {
        $this->backendConfig = $config;
        return $this;
    }

    /**
     * Return the resource configuration as Config object
     *
     * @return  Config
     */
    protected function createResourceConfiguration()
    {
        $config = new Config();
        $config->setSection($this->resourceConfig['name'], $this->resourceConfig);
        return $config;
    }

    /**
     * Return the user backend configuration as Config object
     *
     * @return  Config
     */
    protected function createBackendConfiguration()
    {
        $config = new Config();
        $backendConfig = $this->backendConfig;
        $backendConfig['resource'] = $this->resourceConfig['name'];
        $config->setSection($this->backendConfig['name'], $backendConfig);
        return $config;
    }

    /**
     * Create and add elements to this form
     *
     * @param   array   $formData
     */
    public function createElements(array $formData)
    {
        // LdapUserGroupBackendForm requires these factories to provide valid configurations
        ResourceFactory::setConfig($this->createResourceConfiguration());
        UserBackend::setConfig($this->createBackendConfiguration());

        $backendForm = new LdapUserGroupBackendForm();
        $formData['type'] = 'ldap';
        $backendForm->create($formData);
        $backendForm->getElement('name')->setValue('icingaweb2');
        $this->addSubForm($backendForm, 'backend_form');

        $backendForm->addElement(
            'hidden',
            'resource',
            array(
                'required'      => true,
                'value'         => $this->resourceConfig['name'],
                'decorators'    => array('ViewHelper')
            )
        );
        $backendForm->addElement(
            'hidden',
            'user_backend',
            array(
                'required'      => true,
                'value'         => $this->backendConfig['name'],
                'decorators'    => array('ViewHelper')
            )
        );
    }

    /**
     * Retrieve all form element values
     *
     * @param   bool    $suppressArrayNotation  Ignored
     *
     * @return  array
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues();
        $values = array_merge($values, $values['backend_form']);
        unset($values['backend_form']);
        return $values;
    }
}
