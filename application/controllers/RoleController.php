<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Config;
use Icinga\Exception\AlreadyExistsException;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\ConfirmRemovalForm;
use Icinga\Forms\Security\RoleForm;
use Icinga\Web\Controller\AuthBackendController;
use Icinga\Web\Notification;

/**
 * Manage user permissions and restrictions based on roles
 *
 * @TODO(el): Rename to RolesController: https://dev.icinga.org/issues/10015
 */
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
                } catch (AlreadyExistsException $e) {
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
     */
    public function editAction()
    {
        $this->assertPermission('config/authentication/roles/edit');
        $name = $this->params->getRequired('role');
        $role = new RoleForm();
        $role->setTitle(sprintf($this->translate('Update Role %s'), $name));
        $role->setSubmitLabel($this->translate('Update Role'));
        try {
            $role
                ->setIniConfig(Config::app('roles', true))
                ->load($name);
        } catch (NotFoundError $e) {
            $this->httpNotFound($e->getMessage());
        }
        $role
            ->setOnSuccess(function (RoleForm $role) use ($name) {
                $oldName = $name;
                $name = $role->getElement('name')->getValue();
                $values = $role->getValues();
                try {
                    $role->update($name, $values, $oldName);
                } catch (NotFoundError $e) {
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
     */
    public function removeAction()
    {
        $this->assertPermission('config/authentication/roles/remove');
        $name = $this->params->getRequired('role');
        $role = new RoleForm();
        try {
            $role
                ->setIniConfig(Config::app('roles', true))
                ->load($name);
        } catch (NotFoundError $e) {
            $this->httpNotFound($e->getMessage());
        }
        $confirmation = new ConfirmRemovalForm(array(
            'onSuccess' => function (ConfirmRemovalForm $confirmation) use ($name, $role) {
                try {
                    $role->remove($name);
                } catch (NotFoundError $e) {
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

    /**
     * Create the tabs to display when listing roles
     */
    protected function createListTabs()
    {
        $tabs = $this->getTabs();
        $tabs->add(
            'role/list',
            array(
                'baseTarget'    => '_main',
                'label'         => $this->translate('Roles'),
                'title'         => $this->translate(
                    'Configure roles to permit or restrict users and groups accessing Icinga Web 2'
                ),
                'url'           => 'role/list'

            )
        );
        return $tabs;
    }
}
