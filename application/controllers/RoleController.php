<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

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
 * @TODO(el): Rename to RolesController: https://dev.icinga.com/issues/10015
 */
class RoleController extends AuthBackendController
{
    /**
     * List roles
     *
     * @TODO(el): Rename to indexAction()
     */
    public function listAction()
    {
        $this->assertPermission('config/authentication/roles/show');
        $this->createListTabs()->activate('role/list');
        $this->view->roles = Config::app('roles', true);
    }

    /**
     * Create a new role
     *
     * @TODO(el): Rename to newAction()
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
            ->setSubmitLabel($this->translate('Create Role'))
            ->setIniConfig(Config::app('roles', true))
            ->setRedirectUrl('role/list')
            ->handleRequest();
        $this->renderForm($role, $this->translate('New Role'));
    }

    /**
     * Update a role
     *
     * @TODO(el): Rename to updateAction()
     */
    public function editAction()
    {
        $this->assertPermission('config/authentication/roles/edit');
        $name = $this->params->getRequired('role');
        $role = new RoleForm();
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
        $this->renderForm($role, $this->translate('Update Role'));
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
            ->setSubmitLabel($this->translate('Remove Role'))
            ->setRedirectUrl('role/list')
            ->handleRequest();
        $this->renderForm($confirmation, $this->translate('Remove Role'));
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
        $tabs->add(
            'user/list',
            array(
                'title'     => $this->translate('List users of authentication backends'),
                'label'     => $this->translate('Users'),
                'url'       => 'user/list'
            )
        );
        $tabs->add(
            'group/list',
            array(
                'title'     => $this->translate('List groups of user group backends'),
                'label'     => $this->translate('User Groups'),
                'url'       => 'group/list'
            )
        );
        return $tabs;
    }
}
