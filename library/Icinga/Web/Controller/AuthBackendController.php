<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Controller;

use \Zend_Controller_Action_Exception;
use Icinga\Application\Config;
use Icinga\Authentication\User\UserBackend;
use Icinga\Authentication\User\UserBackendInterface;
use Icinga\Authentication\UserGroup\UserGroupBackend;
use Icinga\Authentication\UserGroup\UserGroupBackendInterface;
use Icinga\Security\SecurityException;
use Icinga\Web\Controller;

/**
 * Base class for authentication backend controllers
 */
class AuthBackendController extends Controller
{
    /**
     * Redirect to the first permitted list action
     */
    final public function indexAction()
    {
        if ($this->hasPermission('config/authentication/users/show')) {
            $this->redirectNow('user/list');
        } elseif ($this->hasPermission('config/authentication/groups/show')) {
            $this->redirectNow('group/list');
        } elseif ($this->hasPermission('config/authentication/roles/show')) {
            $this->redirectNow('role/list');
        } else {
            throw new SecurityException($this->translate('No permission for authentication configuration'));
        }
    }

    /**
     * Return all user backends implementing the given interface
     *
     * @param   string  $interface      The class path of the interface, or null if no interface check should be made
     *
     * @return  array
     */
    protected function loadUserBackends($interface = null)
    {
        $backends = array();
        foreach (Config::app('authentication') as $backendName => $backendConfig) {
            $candidate = UserBackend::create($backendName, $backendConfig);
            if (! $interface || $candidate instanceof $interface) {
                $backends[] = $candidate;
            }
        }

        return $backends;
    }

    /**
     * Return the given user backend or the first match in order
     *
     * @param   string  $name           The name of the backend, or null in case the first match should be returned
     * @param   string  $interface      The interface the backend should implement, no interface check if null
     *
     * @return  UserBackendInterface
     *
     * @throws  Zend_Controller_Action_Exception    In case the given backend name is invalid
     */
    protected function getUserBackend($name = null, $interface = 'Icinga\Data\Selectable')
    {
        if ($name !== null) {
            $config = Config::app('authentication');
            if (! $config->hasSection($name)) {
                $this->httpNotFound(sprintf($this->translate('Authentication backend "%s" not found'), $name));
            } else {
                $backend = UserBackend::create($name, $config->getSection($name));
                if ($interface && !$backend instanceof $interface) {
                    $interfaceParts = explode('\\', strtolower($interface));
                    throw new Zend_Controller_Action_Exception(
                        sprintf(
                            $this->translate('Authentication backend "%s" is not %s'),
                            $name,
                            array_pop($interfaceParts)
                        ),
                        400
                    );
                }
            }
        } else {
            $backends = $this->loadUserBackends($interface);
            $backend = array_shift($backends);
        }

        return $backend;
    }

    /**
     * Return all user group backends implementing the given interface
     *
     * @param   string  $interface      The class path of the interface, or null if no interface check should be made
     *
     * @return  array
     */
    protected function loadUserGroupBackends($interface = null)
    {
        $backends = array();
        foreach (Config::app('groups') as $backendName => $backendConfig) {
            $candidate = UserGroupBackend::create($backendName, $backendConfig);
            if (! $interface || $candidate instanceof $interface) {
                $backends[] = $candidate;
            }
        }

        return $backends;
    }

    /**
     * Return the given user group backend or the first match in order
     *
     * @param   string  $name           The name of the backend, or null in case the first match should be returned
     * @param   string  $interface      The interface the backend should implement, no interface check if null
     *
     * @return  UserGroupBackendInterface
     *
     * @throws  Zend_Controller_Action_Exception    In case the given backend name is invalid
     */
    protected function getUserGroupBackend($name = null, $interface = 'Icinga\Data\Selectable')
    {
        if ($name !== null) {
            $config = Config::app('groups');
            if (! $config->hasSection($name)) {
                $this->httpNotFound(sprintf($this->translate('User group backend "%s" not found'), $name));
            } else {
                $backend = UserGroupBackend::create($name, $config->getSection($name));
                if ($interface && !$backend instanceof $interface) {
                    $interfaceParts = explode('\\', strtolower($interface));
                    throw new Zend_Controller_Action_Exception(
                        sprintf(
                            $this->translate('User group backend "%s" is not %s'),
                            $name,
                            array_pop($interfaceParts)
                        ),
                        400
                    );
                }
            }
        } else {
            $backends = $this->loadUserGroupBackends($interface);
            $backend = array_shift($backends);
        }

        return $backend;
    }

    /**
     * Create the tabs to list users and groups
     */
    protected function createListTabs()
    {
        $tabs = $this->getTabs();

        if ($this->hasPermission('config/authentication/users/show')) {
            $tabs->add(
                'user/list',
                array(
                    'title'     => $this->translate('List users of authentication backends'),
                    'label'     => $this->translate('Users'),
                    'icon'      => 'user',
                    'url'       => 'user/list'
                )
            );
        }

        if ($this->hasPermission('config/authentication/groups/show')) {
            $tabs->add(
                'group/list',
                array(
                    'title'     => $this->translate('List groups of user group backends'),
                    'label'     => $this->translate('Groups'),
                    'icon'      => 'users',
                    'url'       => 'group/list'
                )
            );
        }

        if ($this->hasPermission('config/authentication/roles/show')) {
            $tabs->add(
                'role/list',
                array(
                    'title' => $this->translate(
                        'Configure roles to permit or restrict users and groups accessing Icinga Web 2'
                    ),
                    'label' => $this->translate('Roles'),
                    'url'   => 'role/list'
                )
            );
        }

        return $tabs;
    }
}
