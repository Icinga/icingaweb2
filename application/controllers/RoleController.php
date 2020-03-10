<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Authentication\RolesConfig;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\Security\RoleForm;
use Icinga\Web\Controller\AuthBackendController;

/**
 * Manage user permissions and restrictions based on roles
 *
 * @TODO(el): Rename to RolesController: https://dev.icinga.com/issues/10015
 */
class RoleController extends AuthBackendController
{
    public function init()
    {
        $this->view->title = $this->translate('Roles');

        parent::init();
    }

    /**
     * List roles
     *
     * @TODO(el): Rename to indexAction()
     */
    public function listAction()
    {
        $this->assertPermission('config/authentication/roles/show');
        $this->createListTabs()->activate('role/list');
        $this->view->roles = (new RolesConfig())
            ->select();

        $sortAndFilterColumns = [
            'name'        => $this->translate('Name'),
            'users'       => $this->translate('Users'),
            'groups'      => $this->translate('Groups'),
            'permissions' => $this->translate('Permissions')
        ];

        $this->setupFilterControl($this->view->roles, $sortAndFilterColumns, ['name']);
        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->roles);
        $this->setupSortControl($sortAndFilterColumns, $this->view->roles, ['name']);
    }

    /**
     * Create a new role
     *
     * @TODO(el): Rename to newAction()
     */
    public function addAction()
    {
        $this->assertPermission('config/authentication/roles/add');

        $role = new RoleForm();
        $role->setRedirectUrl('role/list');
        $role->setRepository(new RolesConfig());
        $role->setSubmitLabel($this->translate('Create Role'));
        $role->add()->handleRequest();

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
        $role->setRedirectUrl('role/list');
        $role->setRepository(new RolesConfig());
        $role->setSubmitLabel($this->translate('Update Role'));
        $role->edit($name);

        try {
            $role->handleRequest();
        } catch (NotFoundError $e) {
            $this->httpNotFound($this->translate('Role not found'));
        }

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
        $role->setRedirectUrl('role/list');
        $role->setRepository(new RolesConfig());
        $role->setSubmitLabel($this->translate('Remove Role'));
        $role->remove($name);

        try {
            $role->handleRequest();
        } catch (NotFoundError $e) {
            $this->httpNotFound($this->translate('Role not found'));
        }

        $this->renderForm($role, $this->translate('Remove Role'));
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
