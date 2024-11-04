<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Authentication\AdmissionLoader;
use Icinga\Authentication\Auth;
use Icinga\Authentication\RolesConfig;
use Icinga\Authentication\User\DomainAwareInterface;
use Icinga\Data\Selectable;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\Security\RoleForm;
use Icinga\Repository\Repository;
use Icinga\Security\SecurityException;
use Icinga\User;
use Icinga\Web\Controller\AuthBackendController;
use Icinga\Web\View\PrivilegeAudit;
use Icinga\Web\Widget\SingleValueSearchControl;
use ipl\Html\Html;
use ipl\Html\HtmlString;
use ipl\Web\Url;
use ipl\Web\Widget\Link;

/**
 * Manage user permissions and restrictions based on roles
 *
 * @TODO(el): Rename to RolesController: https://dev.icinga.com/issues/10015
 */
class RoleController extends AuthBackendController
{
    public function init()
    {
        $this->assertPermission('config/access-control/roles');
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
        $this->createListTabs()->activate('role/list');
        $roles = (new RolesConfig())
            ->select();

        $sortAndFilterColumns = [
            'name'        => $this->translate('Name'),
            'users'       => $this->translate('Users'),
            'groups'      => $this->translate('Groups'),
            'permissions' => $this->translate('Permissions')
        ];

        $this->setupFilterControl($roles, $sortAndFilterColumns, ['name']);
        $this->setupLimitControl();
        $this->setupPaginationControl($roles);
        $this->setupSortControl($sortAndFilterColumns, $roles, ['name']);
        $this->view->roles = $roles;
    }

    /**
     * Create a new role
     *
     * @TODO(el): Rename to newAction()
     */
    public function addAction()
    {
        $role = new RoleForm();
        $role->setRedirectUrl('__CLOSE__');
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
        $name = $this->params->getRequired('role');
        $role = new RoleForm();
        $role->setRedirectUrl('__CLOSE__');
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
        $name = $this->params->getRequired('role');
        $role = new RoleForm();
        $role->setRedirectUrl('__CLOSE__');
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
        $this->createListTabs()->activate('role/audit');
        $this->view->title = t('Audit');

        $roleName = $this->params->get('role');
        $type = $this->params->has('group') ? 'group' : 'user';
        $name = $this->params->get($type);

        $backend = null;
        if ($type === 'user') {
            if ($name) {
                $backend = $this->params->getRequired('backend');
            } else {
                $backends = $this->loadUserBackends();
                if (! empty($backends)) {
                    $backend = array_shift($backends)->getName();
                }
            }
        }

        $form = new SingleValueSearchControl();
        $form->setMetaDataNames('type', 'backend');
        $form->populate(['q' => $name, 'q-type' => $type, 'q-backend' => $backend]);
        $form->setInputLabel(t('Enter user or group name'));
        $form->setSubmitLabel(t('Inspect'));
        $form->setSuggestionUrl(Url::fromPath(
            'role/suggest-role-member',
            ['_disableLayout' => true, 'showCompact' => true]
        ));

        $form->on(SingleValueSearchControl::ON_SUCCESS, function ($form) {
            $type = $form->getValue('q-type') ?: 'user';
            $params = [$type => $form->getValue('q')];

            if ($type === 'user') {
                $params['backend'] = $form->getValue('q-backend');
            }

            $this->redirectNow(Url::fromPath('role/audit', $params));
        })->handleRequest(ServerRequest::fromGlobals());

        $this->addControl($form);

        if (! $name) {
            $this->addContent(Html::wantHtml(t('No user or group selected.')));
            return;
        }

        if ($type === 'user') {
            $header = Html::tag('h2', sprintf(t('Privilege Audit for User "%s"'), $name));

            $user = new User($name);
            $user->setAdditional('backend_name', $backend);
            Auth::getInstance()->setupUser($user);
        } else {
            $header = Html::tag('h2', sprintf(t('Privilege Audit for Group "%s"'), $name));

            $user = new User((string) time());
            $user->setGroups([$name]);
            (new AdmissionLoader())->applyRoles($user);
        }

        $chosenRole = null;
        $assignedRoles = array_filter($user->getRoles(), function ($role) use ($user, &$chosenRole, $roleName) {
            if (! in_array($role->getName(), $user->getAdditional('assigned_roles'), true)) {
                return false;
            }

            if ($role->getName() === $roleName) {
                $chosenRole = $role;
            }

            return true;
        });

        $this->addControl(Html::tag(
            'ul',
            ['class' => 'privilege-audit-role-control'],
            [
                Html::tag('li', $roleName ? null : ['class' => 'active'], new Link(
                    t('All roles'),
                    Url::fromRequest()->without('role'),
                    ['class' => 'button-link', 'title' => t('Show privileges of all roles')]
                )),
                array_map(function ($role) use ($roleName) {
                    return Html::tag(
                        'li',
                        $role->getName() === $roleName ? ['class' => 'active'] : null,
                        new Link(
                            $role->getName(),
                            Url::fromRequest()->setParam('role', $role->getName()),
                            [
                                'class' => 'button-link',
                                'title' => sprintf(t('Only show privileges of role %s'), $role->getName())
                            ]
                        )
                    );
                }, $assignedRoles)
            ]
        ));

        $this->addControl($header);
        $this->addContent(
            (new PrivilegeAudit($chosenRole !== null ? [$chosenRole] : $assignedRoles))
                ->addAttributes(['id' => 'role-audit'])
        );
    }

    public function suggestRoleMemberAction()
    {
        $this->assertHttpMethod('POST');
        $requestData = $this->getRequest()->getPost();
        $limit = $this->params->get('limit', 50);

        $searchTerm = $requestData['term']['label'];
        $userBackends = $this->loadUserBackends(Selectable::class);

        $suggestions = [];
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

            $domain = '';
            if ($backend instanceof DomainAwareInterface) {
                $domain = '@' . $backend->getDomain();
            }

            $users = [];
            foreach ($names as $name) {
                $users[] = [$name . $domain, [
                    'type'      => 'user',
                    'backend'   => $backend->getName()
                ]];
            }

            if (! empty($users)) {
                $suggestions[] = [
                    [
                        t('Users'),
                        HtmlString::create('&nbsp;'),
                        Html::tag('span', ['class' => 'badge'], $backend->getName())
                    ],
                    $users
                ];
            }

            $limit -= count($names);
        }

        $groupBackends = $this->loadUserGroupBackends(Selectable::class);

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

            $groups = [];
            foreach ($names as $name) {
                $groups[] = [$name, ['type' => 'group']];
            }

            if (! empty($groups)) {
                $suggestions[] = [
                    [
                        t('Groups'),
                        HtmlString::create('&nbsp;'),
                        Html::tag('span', ['class' => 'badge'], $backend->getName())
                    ],
                    $groups
                ];
            }

            $limit -= count($names);
        }

        if (empty($suggestions)) {
            $suggestions[] = [t('Your search does not match any user or group'), []];
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
                'title'         => $this->translate('Audit a user\'s or group\'s privileges'),
                'label'         => $this->translate('Audit'),
                'url'           => 'role/audit',
                'baseTarget'    => '_main'
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
