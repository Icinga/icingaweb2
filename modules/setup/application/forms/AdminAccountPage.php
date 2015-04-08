<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Forms;

use Exception;
use LogicException;
use Icinga\Web\Form;
use Icinga\Data\ConfigObject;
use Icinga\Data\ResourceFactory;
use Icinga\Authentication\Backend\DbUserBackend;
use Icinga\Authentication\Backend\LdapUserBackend;

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
        $this->setViewScript('form/setup-admin-account.phtml');
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
            $this->addElement(
                'text',
                'by_name',
                array(
                    'required'      => !isset($formData['user_type']) || $formData['user_type'] === 'by_name',
                    'value'         => $this->getUsername(),
                    'label'         => $this->translate('Username'),
                    'description'   => $this->translate(
                        'Define the initial administrative account by providing a username that reflects'
                        . ' a user created later or one that is authenticated using external mechanisms'
                    )
                )
            );
            if (! $this->request->isXmlHttpRequest()) {
                // In case JS is disabled we must not provide client side validation as
                // the user is required to input data even he has changed his mind
                $this->getElement('by_name')->setAttrib('required', null);
                $this->getElement('by_name')->setAttrib('aria-required', null);
            }
        }

        if ($this->backendConfig['backend'] === 'db' || $this->backendConfig['backend'] === 'ldap') {
            $users = $this->fetchUsers();
            if (false === empty($users)) {
                $choices['existing_user'] = $this->translate('Existing User');
                $this->addElement(
                    'select',
                    'existing_user',
                    array(
                        'required'      => isset($formData['user_type']) && $formData['user_type'] === 'existing_user',
                        'label'         => $this->translate('Username'),
                        'description'   => sprintf(
                            $this->translate(
                                'Choose a user reported by the %s backend as the initial administrative account',
                                'setup.admin'
                            ),
                            $this->backendConfig['backend'] === 'db'
                                ? $this->translate('database', 'setup.admin.authbackend')
                                : 'LDAP'
                        ),
                        'multiOptions'  => array_combine($users, $users)
                    )
                );
                if (! $this->request->isXmlHttpRequest()) {
                    // In case JS is disabled we must not provide client side validation as
                    // the user is required to input data even he has changed his mind
                    $this->getElement('existing_user')->setAttrib('required', null);
                    $this->getElement('existing_user')->setAttrib('aria-required', null);
                }
            }
        }

        if ($this->backendConfig['backend'] === 'db') {
            $choices['new_user'] = $this->translate('New User');
            $required = isset($formData['user_type']) && $formData['user_type'] === 'new_user';
            $this->addElement(
                'text',
                'new_user',
                array(
                    'required'      => $required,
                    'label'         => $this->translate('Username'),
                    'description'   => $this->translate(
                        'Enter the username to be used when creating an initial administrative account'
                    )
                )
            );
            $this->addElement(
                'password',
                'new_user_password',
                array(
                    'required'      => $required,
                    'label'         => $this->translate('Password'),
                    'description'   => $this->translate('Enter the password to assign to the newly created account')
                )
            );
            $this->addElement(
                'password',
                'new_user_2ndpass',
                array(
                    'required'      => $required,
                    'label'         => $this->translate('Repeat password'),
                    'description'   => $this->translate('Please repeat the password given above to avoid typing errors'),
                    'validators'    => array(
                        array('identical', false, array('new_user_password'))
                    )
                )
            );
            if (! $this->request->isXmlHttpRequest()) {
                // In case JS is disabled we must not provide client side validation as
                // the user is required to input data even he has changed his mind
                foreach (array('new_user', 'new_user_password', 'new_user_2ndpass') as $elementName) {
                    $this->getElement($elementName)->setAttrib('aria-required', null);
                    $this->getElement($elementName)->setAttrib('required', null);
                }
            }
        }

        if (count($choices) > 1) {
            $this->addElement(
                'radio',
                'user_type',
                array(
                    'required'      => true,
                    'autosubmit'    => true,
                    'value'         => key($choices),
                    'multiOptions'  => $choices
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

        if ($data['user_type'] === 'new_user' && array_search($data['new_user'], $this->fetchUsers()) !== false) {
            $this->getElement('new_user')->addError($this->translate('Username already exists.'));
            return false;
        }

        return true;
    }

    /**
     * Return whether the given values (possibly incomplete) are valid
     *
     * Unsets all empty text-inputs so that they are not being validated when auto-submitting the form.
     *
     * @param   array   $formData
     *
     * @return type
     */
    public function isValidPartial(array $formData)
    {
        foreach (array('by_name', 'new_user', 'new_user_password', 'new_user_2ndpass') as $elementName) {
            if (isset($formData[$elementName]) && $formData[$elementName] === '') {
                unset($formData[$elementName]);
            }
        }

        return parent::isValidPartial($formData);
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
     * Return the names of all users this backend currently provides
     *
     * @return  array
     *
     * @throws  LogicException  In case the backend to fetch users from is not supported
     */
    protected function fetchUsers()
    {
        if ($this->backendConfig['backend'] === 'db') {
            $backend = new DbUserBackend(ResourceFactory::createResource(new ConfigObject($this->resourceConfig)));
        } elseif ($this->backendConfig['backend'] === 'ldap') {
            $backend = new LdapUserBackend(
                ResourceFactory::createResource(new ConfigObject($this->resourceConfig)),
                $this->backendConfig['user_class'],
                $this->backendConfig['user_name_attribute'],
                $this->backendConfig['base_dn'],
                $this->backendConfig['filter']
            );
        } else {
            throw new LogicException(
                sprintf(
                    'Tried to fetch users from an unsupported authentication backend: %s',
                    $this->backendConfig['backend']
                )
            );
        }

        try {
            $users = $backend->listUsers();
            natsort ($users);
            return $users;
        } catch (Exception $e) {
            // No need to handle anything special here. Error means no users found.
            return array();
        }
    }
}
