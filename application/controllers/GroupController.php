<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use \Zend_Controller_Action_Exception;
use Icinga\Application\Config;
use Icinga\Authentication\UserGroup\UserGroupBackend;
use Icinga\Authentication\UserGroup\UserGroupBackendInterface;
use Icinga\Data\Selectable;
use Icinga\Web\Controller;
use Icinga\Web\Widget;

class GroupController extends Controller
{
    /**
     * Initialize this controller
     */
    public function init()
    {
        $this->createTabs();
    }

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
        $backend = $this->getUserGroupBackend($this->params->get('backend'));
        if ($backend === null) {
            $this->view->backend = null;
            return;
        }

        $query = $backend->select(array(
            'group_name',
            'parent_name',
            'created_at',
            'last_modified'
        ));

        $filterEditor = Widget::create('filterEditor')
            ->setQuery($query)
            ->preserveParams('limit', 'sort', 'dir', 'view', 'backend')
            ->ignoreParams('page')
            ->handleRequest($this->getRequest());
        $query->applyFilter($filterEditor->getFilter());
        $this->setupFilterControl($filterEditor);

        $this->getTabs()->activate('group/list');
        $this->view->backend = $backend;
        $this->view->groups = $query->paginate();

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->groups);
        $this->setupSortControl(array(
            'group_name'    => $this->translate('Group'),
            'parent_name'   => $this->translate('Parent'),
            'created_at'    => $this->translate('Created at'),
            'last_modified' => $this->translate('Last modified')
        ));
    }

    /**
     * Return the given user group backend or the first match in order
     *
     * @param   string  $name           The name of the backend, or null in case the first match should be returned
     * @param   bool    $selectable     Whether the backend should implement the Selectable interface
     *
     * @return  UserGroupBackendInterface
     *
     * @throws  Zend_Controller_Action_Exception    In case the given backend name is invalid
     */
    protected function getUserGroupBackend($name = null, $selectable = true)
    {
        $config = Config::app('groups');
        if ($name !== null) {
            if (! $config->hasSection($name)) {
                throw new Zend_Controller_Action_Exception(
                    sprintf($this->translate('User group backend "%s" not found'), $name),
                    404
                );
            } else {
                $backend = UserGroupBackend::create($name, $config->getSection($name));
                if ($selectable && !$backend instanceof Selectable) {
                    throw new Zend_Controller_Action_Exception(
                        sprintf($this->translate('User group backend "%s" is not able to list groups'), $name),
                        400
                    );
                }
            }
        } else {
            $backend = null;
            foreach ($config as $backendName => $backendConfig) {
                $candidate = UserGroupBackend::create($backendName, $backendConfig);
                if (! $selectable || $candidate instanceof Selectable) {
                    $backend = $candidate;
                    break;
                }
            }
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
