<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use \Exception;
use \Zend_Controller_Action_Exception;
use Icinga\Application\Config;
use Icinga\Application\Logger;
use Icinga\Authentication\User\UserBackend;
use Icinga\Authentication\User\UserBackendInterface;
use Icinga\Authentication\UserGroup\UserGroupBackend;
use Icinga\Forms\Config\UserForm;
use Icinga\Data\DataArray\ArrayDatasource;
use Icinga\User;
use Icinga\Web\Controller;
use Icinga\Web\Form;
use Icinga\Web\Notification;
use Icinga\Web\Url;
use Icinga\Web\Widget;

class UserController extends Controller
{
    /**
     * Initialize this controller
     */
    public function init()
    {
        parent::init();
        $this->createTabs();
    }

    /**
     * Redirect to this controller's list action
     */
    public function indexAction()
    {
        $this->redirectNow('user/list');
    }

    /**
     * List all users of a single backend
     */
    public function listAction()
    {
        $backendNames = array_map(
            function ($b) { return $b->getName(); },
            $this->loadUserBackends('Icinga\Data\Selectable')
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
            ->preserveParams('limit', 'sort', 'dir', 'view', 'backend')
            ->ignoreParams('page')
            ->handleRequest($this->getRequest());
        $query->applyFilter($filterEditor->getFilter());
        $this->setupFilterControl($filterEditor);

        try {
            $this->setupPaginationControl($query);
            $this->view->users = $query;
        } catch (Exception $e) {
            Notification::error($e->getMessage());
            Logger::error($e);
        }

        $this->view->backend = $backend;
        $this->getTabs()->activate('user/list');

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

        $this->view->user = $user;
        $this->view->backend = $backend;
        $this->view->memberships = $memberships;

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

    /**
     * Add a user
     */
    public function addAction()
    {
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
        $userName = $this->params->getRequired('user');
        $backend = $this->getUserBackend($this->params->getRequired('backend'), 'Icinga\Data\Updatable');

        $row = $backend->select(array('user_name', 'is_active'))->where('user_name', $userName)->fetchRow();
        if ($row === false) {
            $this->httpNotFound(sprintf($this->translate('User "%s" not found'), $userName));
        }

        $form = new UserForm();
        $form->setRedirectUrl(Url::fromPath('user/show', array('backend' => $backend->getName(), 'user' => $userName)));
        $form->setRepository($backend);
        $form->edit($userName, get_object_vars($row))->handleRequest();

        $this->view->form = $form;
        $this->render('form');
    }

    /**
     * Remove a user
     */
    public function removeAction()
    {
        $userName = $this->params->getRequired('user');
        $backend = $this->getUserBackend($this->params->getRequired('backend'), 'Icinga\Data\Reducible');

        if ($backend->select()->where('user_name', $userName)->count() === 0) {
            $this->httpNotFound(sprintf($this->translate('User "%s" not found'), $userName));
        }

        $form = new UserForm();
        $form->setRedirectUrl(Url::fromPath('user/list', array('backend' => $backend->getName())));
        $form->setRepository($backend);
        $form->remove($userName)->handleRequest();

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
        $groups = array();
        foreach (Config::app('groups') as $backendName => $backendConfig) {
            $backend = UserGroupBackend::create($backendName, $backendConfig);
            foreach ($backend->getMemberships($user) as $groupName) {
                $groups[] = (object) array(
                    'group_name'    => $groupName,
                    'backend'       => $backend
                );
            }
        }

        return new ArrayDatasource($groups);
    }

    /**
     * Return all user backends implementing the given interface
     *
     * @param   string  $interface      The class path of the interface, or null if no interface check should be made
     *
     * @return  array
     */
    protected function loadUserBackends($interface = null)
    {
        $backends = array();
        foreach (Config::app('authentication') as $backendName => $backendConfig) {
            $candidate = UserBackend::create($backendName, $backendConfig);
            if (! $interface || $candidate instanceof $interface) {
                $backends[] = $candidate;
            }
        }

        return $backends;
    }

    /**
     * Return the given user backend or the first match in order
     *
     * @param   string  $name           The name of the backend, or null in case the first match should be returned
     * @param   string  $interface      The interface the backend should implement, no interface check if null
     *
     * @return  UserBackendInterface
     *
     * @throws  Zend_Controller_Action_Exception    In case the given backend name is invalid
     */
    protected function getUserBackend($name = null, $interface = 'Icinga\Data\Selectable')
    {
        if ($name !== null) {
            $config = Config::app('authentication');
            if (! $config->hasSection($name)) {
                $this->httpNotFound(sprintf($this->translate('Authentication backend "%s" not found'), $name));
            } else {
                $backend = UserBackend::create($name, $config->getSection($name));
                if ($interface && !$backend instanceof $interface) {
                    $interfaceParts = explode('\\', strtolower($interface));
                    throw new Zend_Controller_Action_Exception(
                        sprintf(
                            $this->translate('Authentication backend "%s" is not %s'),
                            $name,
                            array_pop($interfaceParts)
                        ),
                        400
                    );
                }
            }
        } else {
            $backends = $this->loadUserBackends($interface);
            $backend = array_shift($backends);
        }

        return $backend;
    }

    /**
     * Create the tabs
     */
    protected function createTabs()
    {
        $tabs = $this->getTabs();
        $tabs->add(
            'user/list',
            array(
                'title'     => $this->translate('List users of authentication backends'),
                'label'     => $this->translate('Users'),
                'icon'      => 'users',
                'url'       => 'user/list'
            )
        );
        $tabs->add(
            'group/list',
            array(
                'title'     => $this->translate('List groups of user group backends'),
                'label'     => $this->translate('Groups'),
                'icon'      => 'cubes',
                'url'       => 'group/list'
            )
        );
    }
}
