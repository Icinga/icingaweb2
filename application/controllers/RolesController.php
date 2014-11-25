<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Config;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Forms\Security\RoleForm;
use Icinga\Web\Controller\ActionController;
use Icinga\Web\Notification;
use Icinga\Web\Widget;

class RolesController extends ActionController
{
    public function init()
    {
        $this->view->tabs = Widget::create('tabs')->add('index', array(
            'title' => $this->translate('Application'),
            'url'   => 'config'
        ))->add('authentication', array(
            'title' => $this->translate('Authentication'),
            'url'   => 'config/authentication'
        ))->add('resources', array(
            'title' => $this->translate('Resources'),
            'url'   => 'config/resource'
        ))->add('permissions', array(
            'title' => $this->translate('Permissions'),
            'url'   => 'permissions'
        ));
    }

    public function indexAction()
    {
        $this->view->tabs->activate('permissions');
        $this->view->roles = Config::app('roles', true);
    }

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
            ->setRedirectUrl('permissions')
            ->handleRequest();
        $this->view->form = $role;
    }

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
            ->setRedirectUrl('permissions')
            ->handleRequest();
        $this->view->name = $name;
        $this->view->form = $role;
    }

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
            ->setRedirectUrl('permissions')
            ->handleRequest();
        $this->view->name = $name;
        $this->view->form = $confirmation;
    }
}
