<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

use \Zend_Controller_Action_Exception;
use Icinga\Application\Config;
use Icinga\Authentication\User\UserBackend;
use Icinga\Authentication\User\UserBackendInterface;
use Icinga\Data\Selectable;
use Icinga\Web\Controller;
use Icinga\Web\Widget;

class UserController extends Controller
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
        $this->redirectNow('user/list');
    }

    /**
     * List all users of a single backend
     */
    public function listAction()
    {
        $backend = $this->getUserBackend($this->params->get('backend'));
        if ($backend === null) {
            $this->view->backend = null;
            return;
        }

        $query = $backend->select(array(
            'user_name',
            'is_active',
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

        $this->getTabs()->activate('user/list');
        $this->view->backend = $backend;
        $this->view->users = $query->paginate();

        $this->setupLimitControl();
        $this->setupPaginationControl($this->view->users);
        $this->setupSortControl(array(
            'user_name'     => $this->translate('Username'),
            'is_active'     => $this->translate('Active'),
            'created_at'    => $this->translate('Created at'),
            'last_modified' => $this->translate('Last modified')
        ));
    }

    /**
     * Return the given user backend or the first match in order
     *
     * @param   string  $name           The name of the backend, or null in case the first match should be returned
     * @param   bool    $selectable     Whether the backend should implement the Selectable interface
     *
     * @return  UserBackendInterface
     *
     * @throws  Zend_Controller_Action_Exception    In case the given backend name is invalid
     */
    protected function getUserBackend($name = null, $selectable = true)
    {
        $config = Config::app('authentication');
        if ($name !== null) {
            if (! $config->hasSection($name)) {
                throw new Zend_Controller_Action_Exception(
                    sprintf($this->translate('Authentication backend "%s" not found'), $name),
                    404
                );
            } else {
                $backend = UserBackend::create($name, $config->getSection($name));
                if ($selectable && !$backend instanceof Selectable) {
                    throw new Zend_Controller_Action_Exception(
                        sprintf($this->translate('Authentication backend "%s" is not able to list users'), $name),
                        400
                    );
                }
            }
        } else {
            $backend = null;
            foreach ($config as $backendName => $backendConfig) {
                $candidate = UserBackend::create($backendName, $backendConfig);
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
    }
}
