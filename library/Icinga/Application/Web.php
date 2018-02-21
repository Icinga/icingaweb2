<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Application;

require_once __DIR__ . '/EmbeddedWeb.php';

use ErrorException;
use Zend_Controller_Action_HelperBroker;
use Zend_Controller_Front;
use Zend_Controller_Router_Route;
use Zend_Layout;
use Zend_Paginator;
use Zend_View_Helper_PaginationControl;
use Icinga\Authentication\Auth;
use Icinga\User;
use Icinga\Util\DirectoryIterator;
use Icinga\Util\TimezoneDetect;
use Icinga\Util\Translator;
use Icinga\Web\Controller\Dispatcher;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Notification;
use Icinga\Web\Session;
use Icinga\Web\Session\Session as BaseSession;
use Icinga\Web\StyleSheet;
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
            ->setupRequest()
            ->setupSession()
            ->setupNotifications()
            ->setupResponse()
            ->setupZendMvc()
            ->setupModuleManager()
            ->loadSetupModuleIfNecessary()
            ->loadEnabledModules()
            ->setupRoute()
            ->setupPagination()
            ->setupUserBackendFactory()
            ->setupUser()
            ->setupTimezone()
            ->setupLogger()
            ->setupInternationalization()
            ->setupFatalErrorHandling();
    }

    /**
     * Get themes provided by Web 2 and all enabled modules
     *
     * @return  string[]    Array of theme names as keys and values
     */
    public function getThemes()
    {
        $themes = array(StyleSheet::DEFAULT_THEME);
        $applicationThemePath = $this->getBaseDir('public/css/themes');
        if (DirectoryIterator::isReadable($applicationThemePath)) {
            foreach (new DirectoryIterator($applicationThemePath, 'less') as $name => $theme) {
                $themes[] = substr($name, 0, -5);
            }
        }
        $mm = $this->getModuleManager();
        foreach ($mm->listEnabledModules() as $moduleName) {
            $moduleThemePath = $mm->getModule($moduleName)->getCssDir() . '/themes';
            if (! DirectoryIterator::isReadable($moduleThemePath)) {
                continue;
            }
            foreach (new DirectoryIterator($moduleThemePath, 'less') as $name => $theme) {
                $themes[] = $moduleName . '/' . substr($name, 0, -5);
            }
        }
        return array_combine($themes, $themes);
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

    private function hasAccessToSharedNavigationItem(& $config, Config $navConfig)
    {
        // TODO: Provide a more sophisticated solution

        if (isset($config['owner']) && strtolower($config['owner']) === strtolower($this->user->getUsername())) {
            unset($config['owner']);
            unset($config['users']);
            unset($config['groups']);
            return true;
        }

        if (isset($config['parent']) && $navConfig->hasSection($config['parent'])) {
            unset($config['owner']);
            if (isset($this->accessibleMenuItems[$config['parent']])) {
                return $this->accessibleMenuItems[$config['parent']];
            }

            $parentConfig = $navConfig->getSection($config['parent']);
            $this->accessibleMenuItems[$config['parent']] = $this->hasAccessToSharedNavigationItem(
                $parentConfig,
                $navConfig
            );
            return $this->accessibleMenuItems[$config['parent']];
        }

        if (isset($config['users'])) {
            $users = array_map('trim', explode(',', strtolower($config['users'])));
            if (in_array('*', $users, true) || in_array(strtolower($this->user->getUsername()), $users, true)) {
                unset($config['owner']);
                unset($config['users']);
                unset($config['groups']);
                return true;
            }
        }

        if (isset($config['groups'])) {
            $groups = array_map('trim', explode(',', strtolower($config['groups'])));
            if (in_array('*', $groups, true)) {
                unset($config['owner']);
                unset($config['users']);
                unset($config['groups']);
                return true;
            }

            $userGroups = array_map('strtolower', $this->user->getGroups());
            $matches = array_intersect($userGroups, $groups);
            if (! empty($matches)) {
                unset($config['owner']);
                unset($config['users']);
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
                if (isset($this->accessibleMenuItems[$name])) {
                    if ($this->accessibleMenuItems[$name]) {
                        $items[$name] = $typeConfig;
                    }
                } else {
                    if ($this->hasAccessToSharedNavigationItem($typeConfig, $config)) {
                        $this->accessibleMenuItems[$name] = true;
                        $items[$name] = $typeConfig;
                    } else {
                        $this->accessibleMenuItems[$name] = false;
                    }
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
                            'icon'        => 'info',
                            'description' => t('Open about page'),
                            'label'       => t('About'),
                            'url'         => 'about',
                            'priority'    => 700
                        ),
                        'announcements' => array(
                            'icon'        => 'megaphone',
                            'description' => t('List announcements'),
                            'label'       => t('Announcements'),
                            'url'         => 'announcements',
                            'priority'    => 710
                        )
                    )
                ),
                'configuration' => array(
                    'label'         => t('Configuration'),
                    'icon'          => 'wrench',
                    'permission'    => 'config/*',
                    'priority'      => 800,
                    'children'      => array(
                        'application' => array(
                            'icon'        => 'wrench',
                            'description' => t('Open application configuration'),
                            'label'       => t('Application'),
                            'url'         => 'config/general',
                            'permission'  => 'config/application/*',
                            'priority'    => 810
                        ),
                        'authentication' => array(
                            'icon'        => 'users',
                            'description' => t('Open authentication configuration'),
                            'label'       => t('Authentication'),
                            'permission'  => 'config/authentication/*',
                            'priority'    => 830,
                            'url'         => 'role/list'
                        ),
                        'navigation' => array(
                            'icon'        => 'sitemap',
                            'description' => t('Open shared navigation configuration'),
                            'label'       => t('Shared Navigation'),
                            'url'         => 'navigation/shared',
                            'permission'  => 'config/application/navigation',
                            'priority'    => 840,
                        ),
                        'modules' => array(
                            'icon'        => 'cubes',
                            'description' => t('Open module configuration'),
                            'label'       => t('Modules'),
                            'url'         => 'config/modules',
                            'permission'  => 'config/modules',
                            'priority'    => 890
                        )
                    )
                ),
                'user' => array(
                    'cssClass'  => 'user-nav-item',
                    'label'     => $this->user->getUsername(),
                    'icon'      => 'user',
                    'priority'  => 900,
                    'children'  => array(
                        'account' => array(
                            'icon'        => 'sliders',
                            'description' => t('Open your account preferences'),
                            'label'       => t('My Account'),
                            'priority'    => 100,
                            'url'         => 'account'
                        ),
                        'logout' => array(
                            'icon'        => 'off',
                            'description' => t('Log out'),
                            'label'       => t('Logout'),
                            'priority'    => 200,
                            'attributes'  => array('target' => '_self'),
                            'url'         => 'authentication/logout'
                        )
                    )
                )
            );

            if (Logger::writesToFile()) {
                $menu['system']['children']['application_log'] = array(
                    'icon'        => 'doc-text',
                    'description' => t(''),
                    'label'       => t('Application Log'),
                    'url'         => 'list/applicationlog',
                    'permission'  => 'application/log',
                    'priority'    => 900
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
        if (! $this->request->isXmlHttpRequest() && $this->request->isApiRequest() && ! $auth->isAuthenticated()) {
            $auth->authHttp();
        }
        if ($auth->isAuthenticated()) {
            $user = $auth->getUser();
            $this->getRequest()->setUser($user);
            $this->user = $user;

            if ($user->can('application/stacktraces')) {
                $displayExceptions = $this->user->getPreferences()->getValue(
                    'icingaweb',
                    'show_stacktraces'
                );

                if ($displayExceptions !== null) {
                    $this->frontController->setParams(
                        array(
                            'displayExceptions' => $displayExceptions
                        )
                    );
                }
            }
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
        // TODO: document what we need for whatever reason?!
        Zend_Paginator::addScrollingStylePrefixPath(
            'Icinga_Web_Paginator_ScrollingStyle_',
            $this->getLibraryDir('Icinga/Web/Paginator/ScrollingStyle')
        );

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
     * Fatal error handling configuration
     *
     * @return $this
     */
    protected function setupFatalErrorHandling()
    {
        register_shutdown_function(function () {
            $error = error_get_last();

            if ($error !== null && $error['type'] === E_ERROR) {
                $frontController = Icinga::app()->getFrontController();
                $response = $frontController->getResponse();

                $response->setException(new ErrorException(
                    $error['message'],
                    0,
                    $error['type'],
                    $error['file'],
                    $error['line']
                ));

                // Clean PHP's fatal error stack trace and replace it with ours
                ob_end_clean();
                $frontController->dispatch($frontController->getRequest(), $response);
            }
        });

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
}
