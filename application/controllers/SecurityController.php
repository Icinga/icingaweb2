<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Config;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Forms\Security\PermissionForm;
use Icinga\Forms\Security\RestrictionForm;
use Icinga\Forms\Security\RoleForm;
use Icinga\Web\Controller\ActionController;
use Icinga\Web\Notification;
use Icinga\Web\Request;

class SecurityController extends ActionController
{
    public function indexAction()
    {
        $this->view->roles = Config::app('roles', true);
    }

    public function newRoleAction()
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
            ->setRedirectUrl('security')
            ->handleRequest();
        $this->view->form = $role;
    }

    public function newPermissionAction()
    {
        $permission = new PermissionForm(array(
            'onSuccess' => function (Request $request, PermissionForm $permission) {
                $name = $permission->getElement('name')->getValue();
                $values = $permission->getValues();
                try {
                    $permission->add($name, $values);
                } catch (InvalidArgumentException $e) {
                    $permission->addError($e->getMessage());
                    return false;
                }
                if ($permission->save()) {
                    Notification::success(t('Permissions granted'));
                    return true;
                }
                return false;
            }
        ));
        $permission
            ->setIniConfig(Config::app('permissions', true))
            ->setRedirectUrl('security')
            ->handleRequest();
        $this->view->form = $permission;
    }

    public function updatePermissionAction()
    {
        $name = $this->_request->getParam('permission');
        if (empty($name)) {
            throw new Zend_Controller_Action_Exception(
                sprintf($this->translate('Required parameter \'%s\' missing'), 'permission'),
                400
            );
        }
        $permission = new PermissionForm();
        try {
            $permission
                ->setIniConfig(Config::app('permissions', true))
                ->load($name);
        } catch (InvalidArgumentException $e) {
            throw new Zend_Controller_Action_Exception(
                $e->getMessage(),
                400
            );
        }
        $permission
            ->setOnSuccess(function (Request $request, PermissionForm $permission) use ($name) {
                $oldName = $name;
                $name = $permission->getElement('name')->getValue();
                $values = $permission->getValues();
                try {
                    $permission->update($name, $values, $oldName);
                } catch (InvalidArgumentException $e) {
                    $permission->addError($e->getMessage());
                    return false;
                }
                if ($permission->save()) {
                    Notification::success(t('Permissions granted'));
                    return true;
                }
                return false;
            })
            ->setRedirectUrl('security')
            ->handleRequest();
        $this->view->name = $name;
        $this->view->form = $permission;
    }

    public function removePermissionAction()
    {
        $name = $this->_request->getParam('permission');
        if (empty($name)) {
            throw new Zend_Controller_Action_Exception(
                sprintf($this->translate('Required parameter \'%s\' missing'), 'permission'),
                400
            );
        }
        $permission = new PermissionForm();
        try {
            $permission
                ->setIniConfig(Config::app('permissions', true))
                ->load($name);
        } catch (InvalidArgumentException $e) {
            throw new Zend_Controller_Action_Exception(
                $e->getMessage(),
                400
            );
        }
        $confirmation = new ConfirmRemovalForm(array(
            'onSuccess' => function (Request $request, ConfirmRemovalForm $confirmation) use ($name, $permission) {
                try {
                    $permission->remove($name);
                } catch (InvalidArgumentException $e) {
                    Notification::error($e->getMessage());
                    return false;
                }
                if ($permission->save()) {
                    Notification::success(sprintf(t('Permission \'%s\' has been successfully removed'), $name));
                    return true;
                }
                return false;
            }
        ));
        $confirmation
            ->setRedirectUrl('security')
            ->handleRequest();
        $this->view->name = $name;
        $this->view->form = $confirmation;
    }

    public function newRestrictionAction()
    {
        $restriction = new RestrictionForm(array(
            'onSuccess' => function (Request $request, RestrictionForm $restriction) {
                $name = $restriction->getElement('name')->getValue();
                $values = $restriction->getValues();
                try {
                    $restriction->add($name, $values);
                } catch (InvalidArgumentException $e) {
                    $restriction->addError($e->getMessage());
                    return false;
                }
                if ($restriction->save()) {
                    Notification::success(t('Restriction set'));
                    return true;
                }
                return false;
            }
        ));
        $restriction
            ->setIniConfig(Config::app('restrictions', true))
            ->setRedirectUrl('security')
            ->handleRequest();
        $this->view->form = $restriction;
    }

    public function updateRestrictionAction()
    {
        $name = $this->_request->getParam('restriction');
        if (empty($name)) {
            throw new Zend_Controller_Action_Exception(
                sprintf($this->translate('Required parameter \'%s\' missing'), 'restriction'),
                400
            );
        }
        $restriction = new RestrictionForm();
        try {
            $restriction
                ->setIniConfig(Config::app('restrictions', true))
                ->load($name);
        } catch (InvalidArgumentException $e) {
            throw new Zend_Controller_Action_Exception(
                $e->getMessage(),
                400
            );
        }
        $restriction
            ->setOnSuccess(function (Request $request, RestrictionForm $restriction) use ($name) {
                $oldName = $name;
                $name = $restriction->getElement('name')->getValue();
                $values = $restriction->getValues();
                try {
                    $restriction->update($name, $values, $oldName);
                } catch (InvalidArgumentException $e) {
                    $restriction->addError($e->getMessage());
                    return false;
                }
                if ($restriction->save()) {
                    Notification::success(t('Restriction set'));
                    return true;
                }
                return false;
            })
            ->setRedirectUrl('security')
            ->handleRequest();
        $this->view->name = $name;
        $this->view->form = $restriction;
    }

    public function removeRestrictionAction()
    {
        $name = $this->_request->getParam('restriction');
        if (empty($name)) {
            throw new Zend_Controller_Action_Exception(
                sprintf($this->translate('Required parameter \'%s\' missing'), 'restriction'),
                400
            );
        }
        $restriction = new RestrictionForm();
        try {
            $restriction
                ->setIniConfig(Config::app('restrictions', true))
                ->load($name);
        } catch (InvalidArgumentException $e) {
            throw new Zend_Controller_Action_Exception(
                $e->getMessage(),
                400
            );
        }
        $confirmation = new ConfirmRemovalForm(array(
            'onSuccess' => function (Request $request, ConfirmRemovalForm $confirmation) use ($name, $restriction) {
                try {
                    $restriction->remove($name);
                } catch (InvalidArgumentException $e) {
                    Notification::error($e->getMessage());
                    return false;
                }
                if ($restriction->save()) {
                    Notification::success(sprintf(t('Restriction \'%s\' has been successfully removed'), $name));
                    return true;
                }
                return false;
            }
        ));
        $confirmation
            ->setRedirectUrl('security')
            ->handleRequest();
        $this->view->name = $name;
        $this->view->form = $confirmation;
    }
}
