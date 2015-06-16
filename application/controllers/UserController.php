<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use Icinga\Application\Logger;
use Icinga\Exception\ConfigurationError;
use Icinga\Exception\NotFoundError;
use Icinga\Forms\Config\User\CreateMembershipForm;
use Icinga\Forms\Config\User\UserForm;
use Icinga\Data\DataArray\ArrayDatasource;
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
            function ($b) { return $b->getName(); },
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
                'label'         => $this->translate('Authentication Backend'),
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
        $filterEditor = Widget::create('filterEditor')
            ->setQuery($query)
            ->setSearchColumns(array('user'))
            ->preserveParams('limit', 'sort', 'dir', 'view', 'backend')
            ->ignoreParams('page')
            ->handleRequest($this->getRequest());
        $query->applyFilter($filterEditor->getFilter());
        $this->setupFilterControl($filterEditor);

        $this->view->users = $query;
        $this->view->backend = $backend;

        $this->setupPaginationControl($query);
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

        $memberships = $this->loadMemberships(new User($userName))->select();

        $filterEditor = Widget::create('filterEditor')
            ->setQuery($memberships)
            ->setSearchColumns(array('group_name'))
            ->preserveParams('limit', 'sort', 'dir', 'view', 'backend', 'user')
            ->ignoreParams('page')
            ->handleRequest($this->getRequest());
        $memberships->applyFilter($filterEditor->getFilter());

        $this->setupFilterControl($filterEditor);
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
                'class'         => 'link-like',
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

        $this->view->form = $form;
        $this->render('form');
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

        $this->view->form = $form;
        $this->render('form');
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

        $this->view->form = $form;
        $this->render('form');
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
                'icon'      => 'user',
                'url'       => Url::fromPath('user/show', array('backend' => $backendName, 'user' => $userName))
            )
        );

        return $tabs;
    }
}
