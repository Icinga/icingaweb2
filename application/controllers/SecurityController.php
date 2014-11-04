<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

use Icinga\Application\Config;
use Icinga\Form\ConfirmRemovalForm;
use Icinga\Form\Security\PermissionForm;
use Icinga\Web\Controller\ActionController;
use Icinga\Web\Notification;
use Icinga\Web\Request;

class SecurityController extends ActionController
{
    public function indexAction()
    {
        $this->view->permissions = Config::app('permissions', true);
        $this->view->restrictions = Config::app('restrictions', true);
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
}
