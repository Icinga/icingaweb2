<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Exception;
use Icinga\Authentication\User\UserBackend;
use Icinga\Authentication\User\DbUserBackend;
use Icinga\Authentication\User\LdapUserBackend;
use Icinga\Data\ConfigObject;
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
     * The backend configuration to use
     *
     * @var array
     */
    protected $backendConfig;

    /**
     * Initialize this page
     */
    public function init()
    {
        $this->setName('setup_admin_account');
        $this->setTitle($this->translate('Administration', 'setup.page.title'));
        $this->addDescription($this->translate(
            'Now it\'s time to configure your first administrative account for Icinga Web 2.'
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
     * Set the backend configuration to use
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
     * @see Form::createElements()
     */
    public function createElements(array $formData)
    {
        $choices = array();
        if ($this->backendConfig['backend'] !== 'db') {
            $choices['by_name'] = $this->translate('By Name', 'setup.admin');
            $choice = isset($formData['user_type']) ? $formData['user_type'] : 'by_name';
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
        if (false === isset($_SERVER['REMOTE_USER'])) {
            return '';
        }

        $name = $_SERVER['REMOTE_USER'];
        if (isset($this->backendConfig['strip_username_regexp']) && $this->backendConfig['strip_username_regexp']) {
            // No need to silence or log anything here because the pattern has
            // already been successfully compiled during backend configuration
            $name = preg_replace($this->backendConfig['strip_username_regexp'], '', $name);
        }

        return $name;
    }

    /**
     * Return the names of all users the backend currently provides
     *
     * @return  array
     */
    protected function fetchUsers()
    {
        try {
            return $this->createBackend()->select(array('user_name'))->order('user_name', 'asc', true)->fetchColumn();
        } catch (Exception $_) {
            // No need to handle anything special here. Error means no users found.
            return array();
        }
    }

    /**
     * Return whether the backend provides a user with the given name
     *
     * @param   string  $username
     *
     * @return  bool
     */
    protected function hasUser($username)
    {
        try {
            return $this->createBackend()->select()->where('user_name', $username)->count() > 1;
        } catch (Exception $_) {
            return null;
        }
    }

    /**
     * Create and return the backend
     *
     * @return  DbUserBackend|LdapUserBackend
     */
    protected function createBackend()
    {
        $config = new ConfigObject($this->backendConfig);
        $config->resource = $this->resourceConfig;
        return UserBackend::create(null, $config);
    }
}
