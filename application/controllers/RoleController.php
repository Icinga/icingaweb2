<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Application\Config;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Forms\Security\RoleForm;
use Icinga\Web\Controller\AuthBackendController;
use Icinga\Web\Notification;

class RoleController extends AuthBackendController
{
    /**
     * List roles
     */
    public function listAction()
    {
        $this->assertPermission('config/authentication/roles/show');
        $this->createListTabs()->activate('role/list');
        $this->view->roles = Config::app('roles', true);
    }

    /**
     * Create a new role
     */
    public function addAction()
    {
        $this->assertPermission('config/authentication/roles/add');
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
            ->setTitle($this->translate('New Role'))
            ->setSubmitLabel($this->translate('Create Role'))
            ->setIniConfig(Config::app('roles', true))
            ->setRedirectUrl('role/list')
            ->handleRequest();
        $this->view->form = $role;
        $this->render('form');
    }

    /**
     * Update a role
     *
     * @throws Zend_Controller_Action_Exception If the required parameter 'role' is missing or the role does not exist
     */
    public function editAction()
    {
        $this->assertPermission('config/authentication/roles/edit');
        $name = $this->_request->getParam('role');
        if (empty($name)) {
            throw new Zend_Controller_Action_Exception(
                sprintf($this->translate('Required parameter \'%s\' missing'), 'role'),
                400
            );
        }
        $role = new RoleForm();
        $role->setTitle(sprintf($this->translate('Update Role %s'), $name));
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
            ->setRedirectUrl('role/list')
            ->handleRequest();
        $this->view->form = $role;
        $this->render('form');
    }

    /**
     * Remove a role
     *
     * @throws Zend_Controller_Action_Exception If the required parameter 'role' is missing or the role does not exist
     */
    public function removeAction()
    {
        $this->assertPermission('config/authentication/roles/remove');
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
            ->setTitle(sprintf($this->translate('Remove Role %s'), $name))
            ->setSubmitLabel($this->translate('Remove Role'))
            ->setRedirectUrl('role/list')
            ->handleRequest();
        $this->view->form = $confirmation;
        $this->render('form');
    }
}
