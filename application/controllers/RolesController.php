<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

use Icinga\Application\Config;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Forms\Security\RoleForm;
use Icinga\Web\Controller\ActionController;
use Icinga\Web\Notification;
use Icinga\Web\Widget;

/**
 * Roles configuration
 */
class RolesController extends ActionController
{
    /**
     * Initialize tabs and validate the user's permissions
     *
     * @throws \Icinga\Security\SecurityException   If the user lacks permissions for configuring roles
     */
    public function init()
    {
        $this->assertPermission('system/config/roles');
        $tabs = $this->getTabs();
        $auth = $this->Auth();
        if ($auth->hasPermission('system/config/application')) {
            $tabs->add('application', array(
                'title' => $this->translate('Application'),
                'url'   => 'config'
            ));
        }
        if ($auth->hasPermission('system/config/authentication')) {
            $tabs->add('authentication', array(
                'title' => $this->translate('Authentication'),
                'url'   => 'config/authentication'
            ));
        }
        if ($auth->hasPermission('system/config/resources')) {
            $tabs->add('resource', array(
                'title' => $this->translate('Resources'),
                'url'   => 'config/resource'
            ));
        }
        $tabs->add('roles', array(
            'title' => $this->translate('Roles'),
            'url'   => 'roles'
        ));
    }

    /**
     * List roles
     */
    public function indexAction()
    {
        $this->view->tabs->activate('roles');
        $this->view->roles = Config::app('roles', true);
    }

    /**
     * Create a new role
     */
    public function newAction()
    {
        $role = new RoleForm(array(
            'onSuccess' => function (RoleForm $role) {
                $name = $role->getElement('name')->getValue();
                $values = $role->getValues();
                try {
                    $role->add($name, $values);
                } catch (InvalidArgumentException $e) {
                    $role->addError($e->getMessage());
                    return false;
                }
                if ($role->save()) {
                    Notification::success(t('Role created'));
                    return true;
                }
                return false;
            }
        ));
        $role
            ->setSubmitLabel($this->translate('Create Role'))
            ->setIniConfig(Config::app('roles', true))
            ->setRedirectUrl('roles')
            ->handleRequest();
        $this->view->form = $role;
    }

    /**
     * Update a role
     *
     * @throws Zend_Controller_Action_Exception If the required parameter 'role' is missing or the role does not exist
     */
    public function updateAction()
    {
        $name = $this->_request->getParam('role');
        if (empty($name)) {
            throw new Zend_Controller_Action_Exception(
                sprintf($this->translate('Required parameter \'%s\' missing'), 'role'),
                400
            );
        }
        $role = new RoleForm();
        $role->setSubmitLabel($this->translate('Update Role'));
        try {
            $role
                ->setIniConfig(Config::app('roles', true))
                ->load($name);
        } catch (InvalidArgumentException $e) {
            throw new Zend_Controller_Action_Exception(
                $e->getMessage(),
                400
            );
        }
        $role
            ->setOnSuccess(function (RoleForm $role) use ($name) {
                $oldName = $name;
                $name = $role->getElement('name')->getValue();
                $values = $role->getValues();
                try {
                    $role->update($name, $values, $oldName);
                } catch (InvalidArgumentException $e) {
                    $role->addError($e->getMessage());
                    return false;
                }
                if ($role->save()) {
                    Notification::success(t('Role updated'));
                    return true;
                }
                return false;
            })
            ->setRedirectUrl('roles')
            ->handleRequest();
        $this->view->name = $name;
        $this->view->form = $role;
    }

    /**
     * Remove a role
     *
     * @throws Zend_Controller_Action_Exception If the required parameter 'role' is missing or the role does not exist
     */
    public function removeAction()
    {
        $name = $this->_request->getParam('role');
        if (empty($name)) {
            throw new Zend_Controller_Action_Exception(
                sprintf($this->translate('Required parameter \'%s\' missing'), 'role'),
                400
            );
        }
        $role = new RoleForm();
        try {
            $role
                ->setIniConfig(Config::app('roles', true))
                ->load($name);
        } catch (InvalidArgumentException $e) {
            throw new Zend_Controller_Action_Exception(
                $e->getMessage(),
                400
            );
        }
        $confirmation = new ConfirmRemovalForm(array(
            'onSuccess' => function (ConfirmRemovalForm $confirmation) use ($name, $role) {
                try {
                    $role->remove($name);
                } catch (InvalidArgumentException $e) {
                    Notification::error($e->getMessage());
                    return false;
                }
                if ($role->save()) {
                    Notification::success(t('Role removed'));
                    return true;
                }
                return false;
            }
        ));
        $confirmation
            ->setSubmitLabel($this->translate('Remove Role'))
            ->setRedirectUrl('roles')
            ->handleRequest();
        $this->view->name = $name;
        $this->view->form = $confirmation;
    }
}
