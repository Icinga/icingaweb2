<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Exception;
use Icinga\Application\Config;
use Icinga\Authentication\User\ExternalBackend;
use Icinga\Authentication\User\UserBackend;
use Icinga\Authentication\User\DbUserBackend;
use Icinga\Authentication\User\LdapUserBackend;
use Icinga\Authentication\UserGroup\UserGroupBackend;
use Icinga\Authentication\UserGroup\LdapUserGroupBackend;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Data\Selectable;
use Icinga\Exception\NotImplementedError;
use Icinga\Web\Form;

/**
 * Wizard page to define the initial administrative account
 */
class AdminAccountPage extends Form
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
     * The user group backend configuration to use
     *
     * @var array
     */
    protected $groupConfig;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_admin_account');
        $this->setTitle($this->translate('Administration', 'setup.page.title'));
        $this->addDescription($this->translate(
            'Now it\'s time to configure your first administrative account or group for Icinga Web 2.'
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
     * Set the user group backend configuration to use
     *
     * @param   array   $config
     *
     * @return  $this
     */
    public function setGroupConfig(array $config = null)
    {
        $this->groupConfig = $config;
        return $this;
    }

    /**
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $choices = array();
        if ($this->backendConfig['backend'] !== 'db') {
            $choices['by_name'] = $this->translate('By Name', 'setup.admin');
            $choice = isset($formData['user_type']) ? $formData['user_type'] : 'by_name';

            if (in_array($this->backendConfig['backend'], array('ldap', 'msldap'))) {
                $groups = $this->fetchGroups();
                if (! empty($groups)) {
                    $choices['user_group'] = $this->translate('User Group', 'setup.admin');
                }
            }
        } else {
            $choices['new_user'] = $this->translate('New User', 'setup.admin');
            $choice = isset($formData['user_type']) ? $formData['user_type'] : 'new_user';
        }

        if (in_array($this->backendConfig['backend'], array('db', 'ldap', 'msldap'))) {
            $users = $this->fetchUsers();
            if (! empty($users)) {
                $choices['existing_user'] = $this->translate('Existing User', 'setup.admin');
            }
        }

        if (count($choices) > 1) {
            $this->addElement(
                'select',
                'user_type',
                array(
                    'required'      => true,
                    'autosubmit'    => true,
                    'label'         => $this->translate('Type Of Definition'),
                    'description'   => $this->translate('Choose how to define the desired account.'),
                    'multiOptions'  => $choices,
                    'value'         => $choice
                )
            );
        } else {
            $this->addElement(
                'hidden',
                'user_type',
                array(
                    'required'  => true,
                    'value'     => key($choices)
                )
            );
        }

        if ($choice === 'by_name') {
            $this->addElement(
                'text',
                'by_name',
                array(
                    'required'      => true,
                    'value'         => $this->getUsername(),
                    'label'         => $this->translate('Username'),
                    'description'   => $this->translate(
                        'Define the initial administrative account by providing a username that reflects'
                        . ' a user created later or one that is authenticated using external mechanisms.'
                    )
                )
            );
        }

        if ($choice === 'user_group') {
            $this->addElement(
                'select',
                'user_group',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Group Name'),
                    'description'   => $this->translate(
                        'Choose a user group reported by the LDAP backend'
                        . ' to permit its members administrative access.',
                        'setup.admin'
                    ),
                    'multiOptions'  => array_combine($groups, $groups)
                )
            );
        }

        if ($choice === 'existing_user') {
            $this->addElement(
                'select',
                'existing_user',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Username'),
                    'description'   => sprintf(
                        $this->translate(
                            'Choose a user reported by the %s backend as the initial administrative account.',
                            'setup.admin'
                        ),
                        $this->backendConfig['backend'] === 'db'
                            ? $this->translate('database', 'setup.admin.authbackend')
                            : 'LDAP'
                    ),
                    'multiOptions'  => array_combine($users, $users)
                )
            );
        }

        if ($choice === 'new_user') {
            $this->addElement(
                'text',
                'new_user',
                array(
                    'required'      => true,
                    'label'         => $this->translate('Username'),
                    'description'   => $this->translate(
                        'Enter the username to be used when creating an initial administrative account.'
                    )
                )
            );
            $this->addElement(
                'password',
                'new_user_password',
                array(
                    'required'          => true,
                    'renderPassword'    => true,
                    'label'             => $this->translate('Password'),
                    'description'       => $this->translate(
                        'Enter the password to assign to the newly created account.'
                    )
                )
            );
            $this->addElement(
                'password',
                'new_user_2ndpass',
                array(
                    'required'          => true,
                    'renderPassword'    => true,
                    'label'             => $this->translate('Repeat password'),
                    'description'       => $this->translate(
                        'Please repeat the password given above to avoid typing errors.'
                    ),
                    'validators'        => array(
                        array('identical', false, array('new_user_password'))
                    )
                )
            );
        }
    }

    /**
     * Validate the given request data and ensure that any new user does not already exist
     *
     * @param   array   $data   The request data to validate
     *
     * @return  bool
     */
    public function isValid($data)
    {
        if (false === parent::isValid($data)) {
            return false;
        }

        if ($data['user_type'] === 'new_user' && $this->hasUser($data['new_user'])) {
            $this->getElement('new_user')->addError($this->translate('Username already exists.'));
            return false;
        }

        return true;
    }

    /**
     * Return the name of the externally authenticated user
     *
     * @return  string
     */
    protected function getUsername()
    {
        list($name, $_) = ExternalBackend::getRemoteUserInformation();
        if ($name === null) {
            return '';
        }

        if (isset($this->backendConfig['strip_username_regexp']) && $this->backendConfig['strip_username_regexp']) {
            // No need to silence or log anything here because the pattern has
            // already been successfully compiled during backend configuration
            $name = preg_replace($this->backendConfig['strip_username_regexp'], '', $name);
        }

        return $name;
    }

    /**
     * Return the names of all users the user backend currently provides
     *
     * @return  array
     */
    protected function fetchUsers()
    {
        try {
            $query = $this
                ->createUserBackend()
                ->select(array('user_name'))
                ->order('user_name', 'asc', true);
            if (in_array($this->backendConfig['backend'], array('ldap', 'msldap'))) {
                $query->getQuery()->setUsePagedResults();
            }

            return $query->fetchColumn();
        } catch (Exception $_) {
            // No need to handle anything special here. Error means no users found.
            return array();
        }
    }

    /**
     * Return whether the user backend provides a user with the given name
     *
     * @param   string  $username
     *
     * @return  bool
     */
    protected function hasUser($username)
    {
        try {
            return $this
                ->createUserBackend()
                ->select()
                ->where('user_name', $username)
                ->count() > 1;
        } catch (Exception $_) {
            return false;
        }
    }

    /**
     * Create and return the user backend
     *
     * @return  DbUserBackend|LdapUserBackend
     */
    protected function createUserBackend()
    {
        $resourceConfig = new Config();
        $resourceConfig->setSection($this->resourceConfig['name'], $this->resourceConfig);
        ResourceFactory::setConfig($resourceConfig);

        $config = new ConfigObject($this->backendConfig);
        $config->resource = $this->resourceConfig['name'];
        return UserBackend::create(null, $config);
    }

    /**
     * Return the names of all user groups the user group backend currently provides
     *
     * @return  array
     */
    protected function fetchGroups()
    {
        try {
            $query = $this
                ->createUserGroupBackend()
                ->select(array('group_name'));
            if (in_array($this->backendConfig['backend'], array('ldap', 'msldap'))) {
                $query->getQuery()->setUsePagedResults();
            }

            return $query->fetchColumn();
        } catch (Exception $_) {
            // No need to handle anything special here. Error means no groups found.
            return array();
        }
    }

    /**
     * Return whether the user group backend provides a user group with the given name
     *
     * @param   string  $groupname
     *
     * @return  bool
     */
    protected function hasGroup($groupname)
    {
        try {
            return $this
                ->createUserGroupBackend()
                ->select()
                ->where('group_name', $groupname)
                ->count() > 1;
        } catch (Exception $_) {
            return false;
        }
    }

    /**
     * Create and return the user group backend
     *
     * @return  LdapUserGroupBackend
     */
    protected function createUserGroupBackend()
    {
        $resourceConfig = new Config();
        $resourceConfig->setSection($this->resourceConfig['name'], $this->resourceConfig);
        ResourceFactory::setConfig($resourceConfig);

        $backendConfig = new Config();
        $backendConfig->setSection($this->backendConfig['name'], array_merge(
            $this->backendConfig,
            array('resource' => $this->resourceConfig['name'])
        ));
        UserBackend::setConfig($backendConfig);

        if (empty($this->groupConfig)) {
            $groupConfig = new ConfigObject(array(
                'backend'       => $this->backendConfig['backend'], // _Should_ be "db" or "msldap"
                'resource'      => $this->resourceConfig['name'],
                'user_backend'  => $this->backendConfig['name'] // Gets ignored if 'backend' is "db"
            ));
        } else {
            $groupConfig = new ConfigObject($this->groupConfig);
        }

        $backend = UserGroupBackend::create(null, $groupConfig);
        if (! $backend instanceof Selectable) {
            throw new NotImplementedError('Unsupported, until #9772 has been resolved');
        }

        return $backend;
    }
}
