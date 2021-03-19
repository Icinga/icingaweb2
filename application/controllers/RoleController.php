<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Authentication\RolesConfig;
use Icinga\Data\Selectable;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\Security\RoleForm;
use Icinga\Repository\Repository;
use Icinga\Security\SecurityException;
use Icinga\Web\Controller\AuthBackendController;
use Icinga\Web\Widget\SingleValueSearchControl;
use ipl\Web\Url;

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

    public function indexAction()
    {
        if ($this->hasPermission('config/access-control/roles')) {
            $this->redirectNow('role/list');
        } elseif ($this->hasPermission('config/access-control/users')) {
            $this->redirectNow('user/list');
        } elseif ($this->hasPermission('config/access-control/groups')) {
            $this->redirectNow('group/list');
        } else {
            throw new SecurityException('No permission to configure Icinga Web 2');
        }
    }

    /**
     * List roles
     *
     * @TODO(el): Rename to indexAction()
     */
    public function listAction()
    {
        $this->assertPermission('config/access-control/roles');
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
        $this->assertPermission('config/access-control/roles');

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
        $this->assertPermission('config/access-control/roles');

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
        $this->assertPermission('config/access-control/roles');

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

    public function auditAction()
    {
        $this->assertPermission('config/access-control/roles');
        $this->createListTabs()->activate('role/audit');

        $type = $this->params->has('group') ? 'group' : 'user';
        $name = $this->params->get($type);

        $form = new SingleValueSearchControl();
        $form->setMetaDataNames('type');
        $form->populate(['q' => $name, 'q-type' => $type]);
        $form->setInputLabel(t('Enter user or group name'));
        $form->setSubmitLabel(t('Inspect'));
        $form->setSuggestionUrl(Url::fromPath(
            'role/suggest-role-member',
            ['_disableLayout' => true, 'showCompact' => true]
        ));

        $form->on(SingleValueSearchControl::ON_SUCCESS, function ($form) {
            $this->redirectNow(Url::fromPath('role/audit', [
                $form->getValue('q-type') ?: 'user' => $form->getValue('q')
            ]));
        })->handleRequest(ServerRequest::fromGlobals());

        $this->content->add($form);
    }

    public function suggestRoleMemberAction()
    {
        $this->assertHttpMethod('POST');
        $requestData = $this->getRequest()->getPost();
        $limit = $this->params->get('limit', 50);

        $searchTerm = $requestData['term']['label'];
        $userBackends = $this->loadUserBackends(Selectable::class);

        $users = [];
        while ($limit > 0 && ! empty($userBackends)) {
            /** @var Repository $backend */
            $backend = array_shift($userBackends);
            $query = $backend->select()
                ->from('user', ['user_name'])
                ->where('user_name', $searchTerm)
                ->limit($limit);

            try {
                $names = $query->fetchColumn();
            } catch (Exception $e) {
                continue;
            }

            foreach ($names as $name) {
                $users[$name] = ['type' => 'user'];
            }

            $limit -= count($names);
        }

        $groupBackends = $this->loadUserGroupBackends(Selectable::class);

        $groups = [];
        while ($limit > 0 && ! empty($groupBackends)) {
            /** @var Repository $backend */
            $backend = array_shift($groupBackends);
            $query = $backend->select()
                ->from('group', ['group_name'])
                ->where('group_name', $searchTerm)
                ->limit($limit);

            try {
                $names = $query->fetchColumn();
            } catch (Exception $e) {
                continue;
            }

            foreach ($names as $name) {
                $groups[$name] = ['type' => 'group'];
            }

            $limit -= count($names);
        }

        $suggestions = [];
        if (! empty($users)) {
            $suggestions[t('Users')] = $users;
        }

        if (! empty($groups)) {
            $suggestions[t('Groups')] = $groups;
        }

        $this->document->add(SingleValueSearchControl::createSuggestions($suggestions));
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
            'role/audit',
            [
                'title'     => $this->translate('Audit a user\'s or group\'s privileges'),
                'label'     => $this->translate('Audit'),
                'url'       => 'role/audit'
            ]
        );

        if ($this->hasPermission('config/access-control/users')) {
            $tabs->add(
                'user/list',
                array(
                    'title'     => $this->translate('List users of authentication backends'),
                    'label'     => $this->translate('Users'),
                    'url'       => 'user/list'
                )
            );
        }

        if ($this->hasPermission('config/access-control/groups')) {
            $tabs->add(
                'group/list',
                array(
                    'title'     => $this->translate('List groups of user group backends'),
                    'label'     => $this->translate('User Groups'),
                    'url'       => 'group/list'
                )
            );
        }

        return $tabs;
    }
}
