<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use \Exception;
use Icinga\Application\Logger;
use Icinga\Data\DataArray\ArrayDatasource;
use Icinga\Data\Reducible;
use Icinga\Data\Filter\Filter;
use Icinga\Forms\Config\UserGroup\AddMemberForm;
use Icinga\Forms\Config\UserGroup\UserGroupForm;
use Icinga\Web\Controller\AuthBackendController;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Url;
use Icinga\Web\Widget;

class GroupController extends AuthBackendController
{
    /**
     * Redirect to this controller's list action
     */
    public function indexAction()
    {
        $this->redirectNow('group/list');
    }

    /**
     * List all user groups of a single backend
     */
    public function listAction()
    {
        $this->assertPermission('config/application/groups/show');
        $backendNames = array_map(
            function ($b) { return $b->getName(); },
            $this->loadUserGroupBackends('Icinga\Data\Selectable')
        );
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
                'label'         => $this->translate('Usergroup Backend'),
                'multiOptions'  => array_combine($backendNames, $backendNames),
                'value'         => $this->params->get('backend')
            )
        );

        $backend = $this->getUserGroupBackend($this->params->get('backend'));
        if ($backend === null) {
            $this->view->backend = null;
            return;
        }

        $query = $backend->select(array('group_name'));
        $filterEditor = Widget::create('filterEditor')
            ->setQuery($query)
            ->preserveParams('limit', 'sort', 'dir', 'view', 'backend')
            ->ignoreParams('page')
            ->handleRequest($this->getRequest());
        $query->applyFilter($filterEditor->getFilter());
        $this->setupFilterControl($filterEditor);

        $this->view->groups = $query;
        $this->view->backend = $backend;
        $this->createListTabs()->activate('group/list');

        $this->setupPaginationControl($query);
        $this->setupLimitControl();
        $this->setupSortControl(
            array(
                'group_name'    => $this->translate('Group'),
                'parent_name'   => $this->translate('Parent'),
                'created_at'    => $this->translate('Created at'),
                'last_modified' => $this->translate('Last modified')
            ),
            $query
        );
    }

    /**
     * Show a group
     */
    public function showAction()
    {
        $this->assertPermission('config/application/groups/show');
        $groupName = $this->params->getRequired('group');
        $backend = $this->getUserGroupBackend($this->params->getRequired('backend'));

        $group = $backend->select(array(
            'group_name',
            'parent_name',
            'created_at',
            'last_modified'
        ))->where('group_name', $groupName)->fetchRow();
        if ($group === false) {
            $this->httpNotFound(sprintf($this->translate('Group "%s" not found'), $groupName));
        }

        $members = $backend
            ->select()
            ->from('group_membership', array('user_name'))
            ->where('group_name', $groupName);

        $filterEditor = Widget::create('filterEditor')
            ->setQuery($members)
            ->preserveParams('limit', 'sort', 'dir', 'view', 'backend', 'group')
            ->ignoreParams('page')
            ->handleRequest($this->getRequest());
        $members->applyFilter($filterEditor->getFilter());

        $this->setupFilterControl($filterEditor);
        $this->setupPaginationControl($members);
        $this->setupLimitControl();
        $this->setupSortControl(
            array(
                'user_name'     => $this->translate('Username'),
                'created_at'    => $this->translate('Created at'),
                'last_modified' => $this->translate('Last modified')
            ),
            $members
        );

        $this->view->group = $group;
        $this->view->backend = $backend;
        $this->view->members = $members;
        $this->createShowTabs($backend->getName(), $groupName)->activate('group/show');

        if ($backend instanceof Reducible) {
            $removeForm = new Form();
            $removeForm->setUidDisabled();
            $removeForm->setAction(
                Url::fromPath('group/removemember', array('backend' => $backend->getName(), 'group' => $groupName))
            );
            $removeForm->addElement('hidden', 'user_name', array(
                'isArray'       => true,
                'decorators'    => array('ViewHelper')
            ));
            $removeForm->addElement('hidden', 'redirect', array(
                'value'         => Url::fromPath('group/show', array(
                    'backend'   => $backend->getName(),
                    'group'     => $groupName
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
                'title'         => $this->translate('Remove this member')
            ));
            $this->view->removeForm = $removeForm;
        }
    }

    /**
     * Add a group
     */
    public function addAction()
    {
        $this->assertPermission('config/application/groups/add');
        $backend = $this->getUserGroupBackend($this->params->getRequired('backend'), 'Icinga\Data\Extensible');
        $form = new UserGroupForm();
        $form->setRedirectUrl(Url::fromPath('group/list', array('backend' => $backend->getName())));
        $form->setRepository($backend);
        $form->add()->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Edit a group
     */
    public function editAction()
    {
        $groupName = $this->params->getRequired('group');
        $backend = $this->getUserGroupBackend($this->params->getRequired('backend'), 'Icinga\Data\Updatable');

        $row = $backend->select(array('group_name'))->where('group_name', $groupName)->fetchRow();
        if ($row === false) {
            $this->httpNotFound(sprintf($this->translate('Group "%s" not found'), $groupName));
        }

        $form = new UserGroupForm();
        $form->setRedirectUrl(
            Url::fromPath('group/show', array('backend' => $backend->getName(), 'group' => $groupName))
        );
        $form->setRepository($backend);
        $form->edit($groupName, get_object_vars($row))->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Remove a group
     */
    public function removeAction()
    {
        $groupName = $this->params->getRequired('group');
        $backend = $this->getUserGroupBackend($this->params->getRequired('backend'), 'Icinga\Data\Reducible');

        if ($backend->select()->where('group_name', $groupName)->count() === 0) {
            $this->httpNotFound(sprintf($this->translate('Group "%s" not found'), $groupName));
        }

        $form = new UserGroupForm();
        $form->setRedirectUrl(Url::fromPath('group/list', array('backend' => $backend->getName())));
        $form->setRepository($backend);
        $form->remove($groupName)->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Add a group member
     */
    public function addmemberAction()
    {
        $groupName = $this->params->getRequired('group');
        $backend = $this->getUserGroupBackend($this->params->getRequired('backend'), 'Icinga\Data\Extensible');

        if ($backend->select()->where('group_name', $groupName)->count() === 0) {
            $this->httpNotFound(sprintf($this->translate('Group "%s" not found'), $groupName));
        }

        $form = new AddMemberForm();
        $form->setDataSource($this->fetchUsers())
            ->setBackend($backend)
            ->setGroupName($groupName)
            ->setRedirectUrl(
                Url::fromPath('group/show', array('backend' => $backend->getName(), 'group' => $groupName))
            )
            ->setUidDisabled()
            ->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Remove a group member
     */
    public function removememberAction()
    {
        $this->assertHttpMethod('POST');
        $groupName = $this->params->getRequired('group');
        $backend = $this->getUserGroupBackend($this->params->getRequired('backend'), 'Icinga\Data\Reducible');

        if ($backend->select()->where('group_name', $groupName)->count() === 0) {
            $this->httpNotFound(sprintf($this->translate('Group "%s" not found'), $groupName));
        }

        $form = new Form(array(
            'onSuccess' => function ($form) use ($groupName, $backend) {
                foreach ($form->getValue('user_name') as $userName) {
                    try {
                        $backend->delete(
                            'group_membership',
                            Filter::matchAll(
                                Filter::where('group_name', $groupName),
                                Filter::where('user_name', $userName)
                            )
                        );
                        Notification::success(sprintf(
                            t('User "%s" has been removed from group "%s"'),
                            $userName,
                            $groupName
                        ));
                    } catch (Exception $e) {
                        Notification::error($e->getMessage());
                    }
                }

                $redirect = $form->getValue('redirect');
                if (! empty($redirect)) {
                    $form->setRedirectUrl(htmlspecialchars_decode($redirect));
                }

                return true;
            }
        ));
        $form->setUidDisabled();
        $form->setSubmitLabel('btn_submit'); // Required to ensure that isSubmitted() is called
        $form->addElement('hidden', 'user_name', array('required' => true, 'isArray' => true));
        $form->addElement('hidden', 'redirect');
        $form->handleRequest();
    }

    /**
     * Fetch and return all users from all user backends
     *
     * @return  ArrayDatasource
     */
    protected function fetchUsers()
    {
        $users = array();
        foreach ($this->loadUserBackends('Icinga\Data\Selectable') as $backend) {
            try {
                foreach ($backend->select(array('user_name')) as $row) {
                    $users[] = $row;
                }
            } catch (Exception $e) {
                Logger::error($e);
                Notification::warning(sprintf(
                    $this->translate('Failed to fetch any users from backend %s. Please check your log'),
                    $backend->getName()
                ));
            }
        }

        return new ArrayDatasource($users);
    }

    /**
     * Create the tabs to display when showing a group
     *
     * @param   string  $backendName
     * @param   string  $groupName
     */
    protected function createShowTabs($backendName, $groupName)
    {
        $tabs = $this->getTabs();
        $tabs->add(
            'group/show',
            array(
                'title'     => sprintf($this->translate('Show group %s'), $groupName),
                'label'     => $this->translate('Group'),
                'icon'      => 'users',
                'url'       => Url::fromPath('group/show', array('backend' => $backendName, 'group' => $groupName))
            )
        );

        return $tabs;
    }
}
