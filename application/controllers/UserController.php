<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Exception;
use Icinga\Application\Logger;
use Icinga\Authentication\User\DomainAwareInterface;
use Icinga\Data\DataArray\ArrayDatasource;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\Config\User\CreateMembershipForm;
use Icinga\Forms\Config\User\UserForm;
use Icinga\User;
use Icinga\Web\Controller\AuthBackendController;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Url;
use Icinga\Web\Widget;

class UserController extends AuthBackendController
{
    /**
     * List all users of a single backend
     */
    public function listAction()
    {
        $this->assertPermission('config/authentication/users/show');
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
        $this->view->backendSelection->setAttrib('class', 'backend-selection');
        $this->view->backendSelection->setUidDisabled();
        $this->view->backendSelection->setMethod('GET');
        $this->view->backendSelection->setTokenDisabled();
        $this->view->backendSelection->addElement(
            'select',
            'backend',
            array(
                'autosubmit'    => true,
                'label'         => $this->translate('User Backend'),
                'multiOptions'  => array_combine($backendNames, $backendNames),
                'value'         => $this->params->get('backend')
            )
        );

        $backend = $this->getUserBackend($this->params->get('backend'));
        if ($backend === null) {
            $this->view->backend = null;
            return;
        }

        $query = $backend->select(array('user_name'));

        $this->view->users = $query;
        $this->view->backend = $backend;

        $this->setupPaginationControl($query);
        $this->setupFilterControl($query);
        $this->setupLimitControl();
        $this->setupSortControl(
            array(
                'user_name'     => $this->translate('Username'),
                'is_active'     => $this->translate('Active'),
                'created_at'    => $this->translate('Created at'),
                'last_modified' => $this->translate('Last modified')
            ),
            $query
        );
    }

    /**
     * Show a user
     */
    public function showAction()
    {
        $this->assertPermission('config/authentication/users/show');
        $userName = $this->params->getRequired('user');
        $backend = $this->getUserBackend($this->params->getRequired('backend'));

        $user = $backend->select(array(
            'user_name',
            'is_active',
            'created_at',
            'last_modified'
        ))->where('user_name', $userName)->fetchRow();
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
            array('group_name' => t('User Group')),
            array('group'),
            array('user')
        );
        $this->setupPaginationControl($memberships);
        $this->setupLimitControl();
        $this->setupSortControl(
            array(
                'group_name' => $this->translate('Group')
            ),
            $memberships
        );

        if ($this->hasPermission('config/authentication/groups/edit')) {
            $extensibleBackends = $this->loadUserGroupBackends('Icinga\Data\Extensible');
            $this->view->showCreateMembershipLink = ! empty($extensibleBackends);
        } else {
            $this->view->showCreateMembershipLink = false;
        }

        $this->view->user = $user;
        $this->view->backend = $backend;
        $this->view->memberships = $memberships;
        $this->createShowTabs($backend->getName(), $userName)->activate('user/show');

        if ($this->hasPermission('config/authentication/groups/edit')) {
            $removeForm = new Form();
            $removeForm->setUidDisabled();
            $removeForm->addElement('hidden', 'user_name', array(
                'isArray'       => true,
                'value'         => $userName,
                'decorators'    => array('ViewHelper')
            ));
            $removeForm->addElement('hidden', 'redirect', array(
                'value'         => Url::fromPath('user/show', array(
                    'backend'   => $backend->getName(),
                    'user'      => $userName
                )),
                'decorators'    => array('ViewHelper')
            ));
            $removeForm->addElement('button', 'btn_submit', array(
                'escape'        => false,
                'type'          => 'submit',
                'class'         => 'link-button spinner',
                'value'         => 'btn_submit',
                'decorators'    => array('ViewHelper'),
                'label'         => $this->view->icon('trash'),
                'title'         => $this->translate('Cancel this membership')
            ));
            $this->view->removeForm = $removeForm;
        }
    }

    /**
     * Add a user
     */
    public function addAction()
    {
        $this->assertPermission('config/authentication/users/add');
        $backend = $this->getUserBackend($this->params->getRequired('backend'), 'Icinga\Data\Extensible');
        $form = new UserForm();
        $form->setRedirectUrl(Url::fromPath('user/list', array('backend' => $backend->getName())));
        $form->setRepository($backend);
        $form->add()->handleRequest();

        $this->renderForm($form, $this->translate('New User'));
    }

    /**
     * Edit a user
     */
    public function editAction()
    {
        $this->assertPermission('config/authentication/users/edit');
        $userName = $this->params->getRequired('user');
        $backend = $this->getUserBackend($this->params->getRequired('backend'), 'Icinga\Data\Updatable');

        $form = new UserForm();
        $form->setRedirectUrl(Url::fromPath('user/show', array('backend' => $backend->getName(), 'user' => $userName)));
        $form->setRepository($backend);

        try {
            $form->edit($userName)->handleRequest();
        } catch (NotFoundError $_) {
            $this->httpNotFound(sprintf($this->translate('User "%s" not found'), $userName));
        }

        $this->renderForm($form, $this->translate('Update User'));
    }

    /**
     * Remove a user
     */
    public function removeAction()
    {
        $this->assertPermission('config/authentication/users/remove');
        $userName = $this->params->getRequired('user');
        $backend = $this->getUserBackend($this->params->getRequired('backend'), 'Icinga\Data\Reducible');

        $form = new UserForm();
        $form->setRedirectUrl(Url::fromPath('user/list', array('backend' => $backend->getName())));
        $form->setRepository($backend);

        try {
            $form->remove($userName)->handleRequest();
        } catch (NotFoundError $_) {
            $this->httpNotFound(sprintf($this->translate('User "%s" not found'), $userName));
        }

        $this->renderForm($form, $this->translate('Remove User'));
    }

    /**
     * Create a membership for a user
     */
    public function createmembershipAction()
    {
        $this->assertPermission('config/authentication/groups/edit');
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
            ->setRedirectUrl(Url::fromPath('user/show', array('backend' => $backend->getName(), 'user' => $userName)))
            ->handleRequest();

        $this->view->form = $form;
        $this->render('form');
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
        $groups = $alreadySeen = array();
        foreach ($this->loadUserGroupBackends() as $backend) {
            try {
                foreach ($backend->getMemberships($user) as $groupName) {
                    if (array_key_exists($groupName, $alreadySeen)) {
                        continue; // Ignore duplicate memberships
                    }

                    $alreadySeen[$groupName] = null;
                    $groups[] = (object) array(
                        'group_name'    => $groupName,
                        'group'         => $groupName,
                        'backend'       => $backend
                    );
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
            array(
                'title'     => sprintf($this->translate('Show user %s'), $userName),
                'label'     => $this->translate('User'),
                'url'       => Url::fromPath('user/show', array('backend' => $backendName, 'user' => $userName))
            )
        );

        return $tabs;
    }

    /**
     * Create the tabs to display when listing users
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
