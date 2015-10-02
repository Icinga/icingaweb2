<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

require_once __DIR__ . '/EmbeddedWeb.php';

use Zend_Controller_Action_HelperBroker;
use Zend_Controller_Front;
use Zend_Controller_Router_Route;
use Zend_Layout;
use Zend_Paginator;
use Zend_View_Helper_PaginationControl;
use Icinga\Authentication\Auth;
use Icinga\User;
use Icinga\Util\TimezoneDetect;
use Icinga\Util\Translator;
use Icinga\Web\Controller\Dispatcher;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use Icinga\Web\Session\Session as BaseSession;
use Icinga\Web\View;

/**
 * Use this if you want to make use of Icinga functionality in other web projects
 *
 * Usage example:
 * <code>
 * use Icinga\Application\Web;
 * Web::start();
 * </code>
 */
class Web extends EmbeddedWeb
{
    /**
     * View object
     *
     * @var View
     */
    private $viewRenderer;

    /**
     * Zend front controller instance
     *
     * @var Zend_Controller_Front
     */
    private $frontController;

    /**
     * Session object
     *
     * @var BaseSession
     */
    private $session;

    /**
     * User object
     *
     * @var User
     */
    private $user;

    /**
     * Identify web bootstrap
     *
     * @var bool
     */
    protected $isWeb = true;

    /**
     * Initialize all together
     *
     * @return $this
     */
    protected function bootstrap()
    {
        return $this
            ->setupZendAutoloader()
            ->setupLogging()
            ->setupErrorHandling()
            ->loadConfig()
            ->setupResourceFactory()
            ->setupSession()
            ->setupNotifications()
            ->setupRequest()
            ->setupResponse()
            ->setupUserBackendFactory()
            ->setupUser()
            ->setupTimezone()
            ->setupLogger()
            ->setupInternationalization()
            ->setupZendMvc()
            ->setupNamespaces()
            ->setupModuleManager()
            ->loadSetupModuleIfNecessary()
            ->loadEnabledModules()
            ->setupRoute()
            ->setupPagination();
    }

    /**
     * Prepare routing
     *
     * @return $this
     */
    private function setupRoute()
    {
        $this->frontController->getRouter()->addRoute(
            'module_javascript',
            new Zend_Controller_Router_Route(
                'js/components/:module_name/:file',
                array(
                    'controller' => 'static',
                    'action'     => 'javascript'
                )
            )
        );

        return $this;
    }

    /**
     * Getter for frontController
     *
     * @return Zend_Controller_Front
     */
    public function getFrontController()
    {
        return $this->frontController;
    }

    /**
     * Getter for view
     *
     * @return View
     */
    public function getViewRenderer()
    {
        return $this->viewRenderer;
    }

    private function hasAccessToSharedNavigationItem(& $config)
    {
        // TODO: Provide a more sophisticated solution

        if (isset($config['owner']) && $config['owner'] === $this->user->getUsername()) {
            unset($config['owner']);
            return true;
        }

        if (isset($config['users'])) {
            $users = array_map('trim', explode(',', strtolower($config['users'])));
            if (in_array('*', $users, true) || in_array($this->user->getUsername(), $users, true)) {
                unset($config['users']);
                return true;
            }
        }

        if (isset($config['groups'])) {
            $groups = array_map('trim', explode(',', strtolower($config['groups'])));
            if (in_array('*', $groups, true)) {
                unset($config['groups']);
                return true;
            }

            $userGroups = array_map('strtolower', $this->user->getGroups());
            $matches = array_intersect($userGroups, $groups);
            if (! empty($matches)) {
                unset($config['groups']);
                return true;
            }
        }

        return false;
    }

    /**
     * Load and return the shared navigation of the given type
     *
     * @param   string  $type
     *
     * @return  Navigation
     */
    public function getSharedNavigation($type)
    {
        $config = Config::navigation($type === 'dashboard-pane' ? 'dashlet' : $type);

        if ($type === 'dashboard-pane') {
            $panes = array();
            foreach ($config as $dashletName => $dashletConfig) {
                if ($this->hasAccessToSharedNavigationItem($dashletConfig)) {
                    // TODO: Throw ConfigurationError if pane or url is missing
                    $panes[$dashletConfig->pane][$dashletName] = $dashletConfig->url;
                }
            }

            $navigation = new Navigation();
            foreach ($panes as $paneName => $dashlets) {
                $navigation->addItem(
                    $paneName,
                    array(
                        'type'      => 'dashboard-pane',
                        'dashlets'  => $dashlets
                    )
                );
            }
        } else {
            $items = array();
            foreach ($config as $name => $typeConfig) {
                if ($this->hasAccessToSharedNavigationItem($typeConfig)) {
                    $items[$name] = $typeConfig;
                }
            }

            $navigation = Navigation::fromConfig($items);
        }

        return $navigation;
    }

    /**
     * Return the app's menu
     *
     * @return  Navigation
     */
    public function getMenu()
    {
        if ($this->user !== null) {
            $menu = array(
                'dashboard' => array(
                    'label'     => t('Dashboard'),
                    'url'       => 'dashboard',
                    'icon'      => 'dashboard',
                    'priority'  => 10
                ),
                'system' => array(
                    'label'     => t('System'),
                    'icon'      => 'services',
                    'priority'  => 700,
                    'renderer'  => array(
                        'SummaryNavigationItemRenderer',
                        'state' => 'critical'
                    ),
                    'children'  => array(
                        'about' => array(
                            'label'     => t('About'),
                            'url'       => 'about',
                            'priority'  => 701
                        )
                    )
                ),
                'configuration' => array(
                    'label'         => t('Configuration'),
                    'icon'          => 'wrench',
                    'permission'    => 'config/*',
                    'priority'      => 800,
                    'children'      => array(
                        'application'       => array(
                            'label'         => t('Application'),
                            'url'           => 'config/general',
                            'permission'    => 'config/application/*',
                            'priority'      => 810
                        ),
                        'navigation'        => array(
                            'label'         => t('Shared Navigation'),
                            'url'           => 'navigation/shared',
                            'permission'    => 'config/application/navigation',
                            'priority'      => 820,
                        ),
                        'authentication'    => array(
                            'label'         => t('Authentication'),
                            'url'           => 'config/userbackend',
                            'permission'    => 'config/authentication/*',
                            'priority'      => 830
                        ),
                        'roles'             => array(
                            'label'         => t('Roles'),
                            'url'           => 'role/list',
                            'permission'    => 'config/authentication/roles/show',
                            'priority'      => 840
                        ),
                        'users'             => array(
                            'label'         => t('Users'),
                            'url'           => 'user/list',
                            'permission'    => 'config/authentication/users/show',
                            'priority'      => 850
                        ),
                        'groups'            => array(
                            'label'         => t('Usergroups'),
                            'url'           => 'group/list',
                            'permission'    => 'config/authentication/groups/show',
                            'priority'      => 860
                        ),
                        'modules'           => array(
                            'label'         => t('Modules'),
                            'url'           => 'config/modules',
                            'permission'    => 'config/modules',
                            'priority'      => 890
                        )
                    )
                ),
                'user' => array(
                    'label'     => $this->user->getUsername(),
                    'icon'      => 'user',
                    'priority'  => 900,
                    'children'  => array(
                        'preferences'   => array(
                            'label'     => t('Preferences'),
                            'url'       => 'preference',
                            'priority'  => 910
                        ),
                        'navigation'    => array(
                            'label'     => t('Navigation'),
                            'url'       => 'navigation',
                            'priority'  => 920
                        ),
                        'logout'        => array(
                            'label'     => t('Logout'),
                            'url'       => 'authentication/logout',
                            'priority'  => 990,
                            'renderer'  => array(
                                'NavigationItemRenderer',
                                'target' => '_self'
                            )
                        )
                    )
                )
            );

            if (Logger::writesToFile()) {
                $menu['system']['children']['application_log'] = array(
                    'label'     => t('Application Log'),
                    'url'       => 'list/applicationlog',
                    'priority'  => 710
                );
            }
        } else {
            $menu = array();
        }

        return Navigation::fromArray($menu)->load('menu-item');
    }

    /**
     * Dispatch public interface
     */
    public function dispatch()
    {
        $this->frontController->dispatch($this->getRequest(), $this->getResponse());
    }

    /**
     * Prepare Zend MVC Base
     *
     * @return $this
     */
    private function setupZendMvc()
    {
        Zend_Layout::startMvc(
            array(
                'layout'     => 'layout',
                'layoutPath' => $this->getApplicationDir('/layouts/scripts')
            )
        );
        $this->setupFrontController();
        $this->setupViewRenderer();
        return $this;
    }

    /**
     * Create user object
     *
     * @return $this
     */
    private function setupUser()
    {
        $auth = Auth::getInstance();
        if ($auth->isAuthenticated()) {
            $user = $auth->getUser();
            $this->getRequest()->setUser($user);
            $this->user = $user;
        }
        return $this;
    }

    /**
     * Initialize a session provider
     *
     * @return $this
     */
    private function setupSession()
    {
        $this->session = Session::create();
        return $this;
    }

    /**
     * Initialize notifications to remove them immediately from session
     *
     * @return $this
     */
    private function setupNotifications()
    {
        Notification::getInstance();
        return $this;
    }

    /**
     * Instantiate front controller
     *
     * @return $this
     */
    private function setupFrontController()
    {
        $this->frontController = Zend_Controller_Front::getInstance();
        $this->frontController->setDispatcher(new Dispatcher());
        $this->frontController->setRequest($this->getRequest());
        $this->frontController->setControllerDirectory($this->getApplicationDir('/controllers'));

        $displayExceptions = $this->config->get('global', 'show_stacktraces', true);
        if ($this->user !== null && $this->user->can('application/stacktraces')) {
            $displayExceptions = $this->user->getPreferences()->getValue(
                'icingaweb',
                'show_stacktraces',
                $displayExceptions
            );
        }

        $this->frontController->setParams(
            array(
                'displayExceptions' => $displayExceptions
            )
        );
        return $this;
    }

    /**
     * Register helper paths and views for renderer
     *
     * @return $this
     */
    private function setupViewRenderer()
    {
        $view = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        /** @var \Zend_Controller_Action_Helper_ViewRenderer $view */
        $view->setView(new View());
        $view->view->addHelperPath($this->getApplicationDir('/views/helpers'));
        $view->view->setEncoding('UTF-8');
        $view->view->headTitle()->prepend($this->config->get('global', 'project', 'Icinga'));
        $view->view->headTitle()->setSeparator(' :: ');
        $this->viewRenderer = $view;
        return $this;
    }

    /**
     * Configure pagination settings
     *
     * @return $this
     */
    private function setupPagination()
    {
        Zend_Paginator::addScrollingStylePrefixPath(
            'Icinga_Web_Paginator_ScrollingStyle',
            'Icinga/Web/Paginator/ScrollingStyle'
        );
        Zend_Paginator::setDefaultScrollingStyle('SlidingWithBorder');
        Zend_View_Helper_PaginationControl::setDefaultViewPartial(
            array('mixedPagination.phtml', 'default')
        );
        return $this;
    }

    /**
     * (non-PHPDoc)
     * @see ApplicationBootstrap::detectTimezone() For the method documentation.
     */
    protected function detectTimezone()
    {
        $auth = Auth::getInstance();
        if (! $auth->isAuthenticated()
            || ($timezone = $auth->getUser()->getPreferences()->getValue('icingaweb', 'timezone')) === null
        ) {
            $detect = new TimezoneDetect();
            $timezone = $detect->getTimezoneName();
        }
        return $timezone;
    }

    /**
     * Setup internationalization using gettext
     *
     * Uses the preferred user language or the browser suggested language or our default.
     *
     * @return  string                      Detected locale code
     *
     * @see     Translator::DEFAULT_LOCALE  For the the default locale code.
     */
    protected function detectLocale()
    {
        $auth = Auth::getInstance();
        if ($auth->isAuthenticated()
            && ($locale = $auth->getUser()->getPreferences()->getValue('icingaweb', 'language')) !== null
        ) {
            return $locale;
        }
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return Translator::getPreferredLocaleCode($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        }
        return Translator::DEFAULT_LOCALE;
    }

    /**
     * Setup class loader namespaces for Icinga\Controllers and Icinga\Forms
     *
     * @return $this
     */
    private function setupNamespaces()
    {
        $this
            ->getLoader()
            ->registerNamespace(
                'Icinga\\' . Dispatcher::CONTROLLER_NAMESPACE,
                $this->getApplicationDir('controllers')
            )
            ->registerNamespace(
                'Icinga\\Forms',
                $this->getApplicationDir('forms')
            );
        return $this;
    }
}
