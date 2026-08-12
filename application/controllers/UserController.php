<?php

// SPDX-FileCopyrightText: 2018 Icinga GmbH <https://icinga.com>
// SPDX-License-Identifier: GPL-3.0-or-later

namespace Icinga\Controllers;

use Exception;
use GuzzleHttp\Psr7\ServerRequest;
use Icinga\Application\Logger;
use Icinga\Authentication\AdmissionLoader;
use Icinga\Authentication\User\DbUserBackend;
use Icinga\Authentication\User\DomainAwareInterface;
use Icinga\Data\DataArray\ArrayDatasource;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\Config\User\CreateMembershipForm;
use Icinga\Forms\Config\User\UserForm;
use Icinga\Repository\RepositoryMode;
use Icinga\User;
use Icinga\Web\Controller\AuthBackendController;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use ipl\Html\Contract\Form as IplForm;
use ipl\Web\Url;
use Throwable;

class UserController extends AuthBackendController
{
    public function init()
    {
        $this->view->title = $this->translate('Users');

        parent::init();
    }

    /**
     * List all users of a single backend
     */
    public function listAction()
    {
        $this->assertPermission('config/access-control/users');
        $this->createListTabs()->activate('user/list');
        $backendNames = array_map(
            function ($b) {
                return $b->getName();
            },
            $this->loadUserBackends('Icinga\Data\Selectable')
        );
        if (empty($backendNames)) {
            return;
        }

        $this->view->backendSelection = new Form();
        $this->view->backendSelection->setAttrib('class', 'backend-selection icinga-controls');
        $this->view->backendSelection->setUidDisabled();
        $this->view->backendSelection->setMethod('GET');
        $this->view->backendSelection->setTokenDisabled();
        $this->view->backendSelection->addElement(
            'select',
            'backend',
            [
                'autosubmit'    => true,
                'label'         => $this->translate('User Backend'),
                'multiOptions'  => array_combine($backendNames, $backendNames),
                'value'         => $this->params->get('backend')
            ]
        );

        $backend = $this->getUserBackend($this->params->get('backend'));
        if ($backend === null) {
            $this->view->backend = null;
            return;
        }

        $query = $backend->select(['user_name']);

        $this->view->users = $query;
        $this->view->backend = $backend;

        $this->setupPaginationControl($query);
        $this->setupFilterControl($query);
        $this->setupLimitControl();
        $this->setupSortControl(
            [
                'user_name'     => $this->translate('Username'),
                'is_active'     => $this->translate('Active'),
                'created_at'    => $this->translate('Created at'),
                'last_modified' => $this->translate('Last modified')
            ],
            $query
        );
    }

    /**
     * Show a user
     */
    public function showAction()
    {
        $this->assertPermission('config/access-control/users');
        $userName = $this->params->getRequired('user');
        $backend = $this->getUserBackend($this->params->getRequired('backend'));

        $user = $backend->select([
            'user_name',
            'is_active',
            'created_at',
            'last_modified'
        ])->where('user_name', $userName)->fetchRow();
        if ($user === false) {
            $this->httpNotFound(sprintf($this->translate('User "%s" not found'), $userName));
        }

        $userObj = new User($userName);
        if ($backend instanceof DomainAwareInterface) {
            $userObj->setDomain($backend->getDomain());
        }

        $memberships = $this->loadMemberships($userObj)->select();

        $this->setupFilterControl(
            $memberships,
            ['group_name' => t('User Group')],
            ['group'],
            ['user']
        );
        $this->setupPaginationControl($memberships);
        $this->setupLimitControl();
        $this->setupSortControl(
            [
                'group_name' => $this->translate('Group')
            ],
            $memberships
        );

        if ($this->hasPermission('config/access-control/groups')) {
            $extensibleBackends = $this->loadUserGroupBackends('Icinga\Data\Extensible');
            $this->view->showCreateMembershipLink = ! empty($extensibleBackends);
        } else {
            $this->view->showCreateMembershipLink = false;
        }

        $this->view->user = $user;
        $this->view->backend = $backend;
        $this->view->memberships = $memberships;
        $this->createShowTabs($backend->getName(), $userName)->activate('user/show');

        if ($this->hasPermission('config/access-control/groups')) {
            $removeForm = new Form();
            $removeForm->setUidDisabled();
            $removeForm->setAttrib('class', 'inline');
            $removeForm->addElement('hidden', 'user_name', [
                'isArray'       => true,
                'value'         => $userName,
                'decorators'    => ['ViewHelper']
            ]);
            $removeForm->addElement('hidden', 'redirect', [
                'value'         => Url::fromPath('user/show', [
                    'backend'   => $backend->getName(),
                    'user'      => $userName
                ]),
                'decorators'    => ['ViewHelper']
            ]);
            $removeForm->addElement('button', 'btn_submit', [
                'escape'        => false,
                'type'          => 'submit',
                'class'         => 'link-button spinner',
                'value'         => 'btn_submit',
                'decorators'    => ['ViewHelper'],
                'label'         => $this->view->icon('cancel'),
                'title'         => $this->translate('Cancel this membership')
            ]);
            $this->view->removeForm = $removeForm;
        }

        $admissionLoader = new AdmissionLoader();
        $admissionLoader->applyRoles($userObj);
        $this->view->userObj = $userObj;
        $this->view->allowedToEditRoles = $this->hasPermission('config/access-control/groups');
    }

    /**
     * Add a user
     */
    public function addAction(): void
    {
        $this->assertPermission('config/access-control/users');

        /** @var DbUserBackend $backend */
        $backend = $this->getUserBackend($this->params->getRequired('backend'), 'Icinga\Data\Extensible');

        $form = (new UserForm($backend, RepositoryMode::Insert))
            ->setCsrfCounterMeasureId(Session::getSession()->getId())
            ->setRedirectUrl(Url::fromPath('user/list', ['backend' => $backend->getName()]))
            ->on(IplForm::ON_SUBMIT, function (UserForm $form): void {
                Notification::success($this->translate('User created'));
                $this->redirectNow($form->getRedirectUrl());
            })
            ->on(IplForm::ON_ERROR, function (Throwable $_, UserForm $_form): void {
                Notification::error($this->translate('Failed to create user'));
            })
            ->handleRequest(ServerRequest::fromGlobals());

        $this->addTitleTab($this->translate('New User'));
        $this->addContent($form);
    }

    /**
     * Edit a user
     */
    public function editAction(): void
    {
        $this->assertPermission('config/access-control/users');

        /** @var DbUserBackend $backend */
        $backend = $this->getUserBackend($this->params->getRequired('backend'), 'Icinga\Data\Updatable');
        $userName = $this->params->getRequired('user');

        $form = (new UserForm($backend, RepositoryMode::Update, $userName))
            ->setCsrfCounterMeasureId(Session::getSession()->getId())
            ->setRedirectUrl(Url::fromPath('user/show', ['backend' => $backend->getName(), 'user' => $userName]))
            ->on(IplForm::ON_SUBMIT, function (UserForm $form): void {
                Notification::success(sprintf($this->translate('User "%s" has been updated'), $form->getIdentifier()));

                /** @var Url $redirect */
                $redirect = $form->getRedirectUrl();
                $newName = $form->getValue('user_name');
                if ($newName !== $form->getIdentifier()) {
                    $redirect->setParam('user', $newName);
                }

                $this->sendExtraUpdates(['#col1']);
                $this->redirectNow($redirect);
            })
            ->on(IplForm::ON_ERROR, function (Throwable $_, UserForm $form): void {
                Notification::error(sprintf($this->translate('Failed to update user "%s"'), $form->getIdentifier()));
            });

        try {
            $form->handleRequest(ServerRequest::fromGlobals());
        } catch (NotFoundError) {
            $this->httpNotFound(sprintf($this->translate('User "%s" not found'), $userName));
        }

        $this->addTitleTab($this->translate('Update User'));
        $this->addContent($form);
    }

    /**
     * Remove a user
     */
    public function removeAction(): void
    {
        $this->assertPermission('config/access-control/users');

        /** @var DbUserBackend $backend */
        $backend = $this->getUserBackend($this->params->getRequired('backend'), 'Icinga\Data\Reducible');
        $userName = $this->params->getRequired('user');

        $form = (new UserForm($backend, RepositoryMode::Delete, $userName))
            ->setCsrfCounterMeasureId(Session::getSession()->getId())
            ->setRedirectUrl(Url::fromPath('user/list', ['backend' => $backend->getName()]))
            ->on(IplForm::ON_SUBMIT, function (UserForm $form): void {
                Notification::success(sprintf($this->translate('User "%s" has been removed'), $form->getIdentifier()));
                $this->redirectNow($form->getRedirectUrl());
            })
            ->on(IplForm::ON_ERROR, function (Throwable $_, UserForm $form): void {
                Notification::error(sprintf($this->translate('Failed to remove user "%s"'), $form->getIdentifier()));
            });

        try {
            $form->handleRequest(ServerRequest::fromGlobals());
        } catch (NotFoundError) {
            $this->httpNotFound(sprintf($this->translate('User "%s" not found'), $userName));
        }

        $this->addTitleTab($this->translate('Remove User'));
        $this->addContent($form);
    }

    /**
     * Create a membership for a user
     */
    public function createmembershipAction()
    {
        $this->assertPermission('config/access-control/groups');
        $userName = $this->params->getRequired('user');
        $backend = $this->getUserBackend($this->params->getRequired('backend'));

        if ($backend->select()->where('user_name', $userName)->count() === 0) {
            $this->httpNotFound(sprintf($this->translate('User "%s" not found'), $userName));
        }

        $backends = $this->loadUserGroupBackends('Icinga\Data\Extensible');
        if (empty($backends)) {
            throw new ConfigurationError($this->translate(
                'You\'ll need to configure at least one user group backend first that allows to create new memberships'
            ));
        }

        $form = new CreateMembershipForm();
        $form->setBackends($backends)
            ->setUsername($userName)
            ->setRedirectUrl(Url::fromPath('user/show', ['backend' => $backend->getName(), 'user' => $userName]))
            ->handleRequest();

        $this->renderForm($form, $this->translate('Create New Membership'));
    }

    /**
     * Fetch and return the given user's groups from all user group backends
     *
     * @param   User    $user
     *
     * @return  ArrayDatasource
     */
    protected function loadMemberships(User $user)
    {
        $groups = $alreadySeen = [];
        foreach ($this->loadUserGroupBackends() as $backend) {
            try {
                foreach ($backend->getMemberships($user) as $groupName) {
                    if (array_key_exists($groupName, $alreadySeen)) {
                        continue; // Ignore duplicate memberships
                    }

                    $alreadySeen[$groupName] = null;
                    $groups[] = (object) [
                        'group_name'    => $groupName,
                        'group'         => $groupName,
                        'backend'       => $backend
                    ];
                }
            } catch (Exception $e) {
                Logger::error($e);
                Notification::warning(sprintf(
                    $this->translate('Failed to fetch memberships from backend %s. Please check your log'),
                    $backend->getName()
                ));
            }
        }

        return new ArrayDatasource($groups);
    }

    /**
     * Create the tabs to display when showing a user
     *
     * @param   string  $backendName
     * @param   string  $userName
     */
    protected function createShowTabs($backendName, $userName)
    {
        $tabs = $this->getTabs();
        $tabs->add(
            'user/show',
            [
                'title'     => sprintf($this->translate('Show user %s'), $userName),
                'label'     => $this->translate('User'),
                'url'       => Url::fromPath('user/show', ['backend' => $backendName, 'user' => $userName])
            ]
        );

        return $tabs;
    }

    /**
     * Create the tabs to display when listing users
     */
    protected function createListTabs()
    {
        $tabs = $this->getTabs();

        if ($this->hasPermission('config/access-control/roles')) {
            $tabs->add(
                'role/list',
                [
                    'baseTarget'    => '_main',
                    'label'         => $this->translate('Roles'),
                    'title'         => $this->translate(
                        'Configure roles to permit or restrict users and groups accessing Icinga Web 2'
                    ),
                    'url'           => 'role/list'
                ]
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
        }

        $tabs->add(
            'user/list',
            [
                'title'     => $this->translate('List users of authentication backends'),
                'label'     => $this->translate('Users'),
                'url'       => 'user/list'
            ]
        );

        if ($this->hasPermission('config/access-control/groups')) {
            $tabs->add(
                'group/list',
                [
                    'title'     => $this->translate('List groups of user group backends'),
                    'label'     => $this->translate('User Groups'),
                    'url'       => 'group/list'
                ]
            );
        }

        return $tabs;
    }
}
