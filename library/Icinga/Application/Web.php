<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use \DateTimeZone;
use \Exception;
use \Zend_Layout;
use \Zend_Paginator;
use \Zend_View_Helper_PaginationControl;
use \Zend_Controller_Action_HelperBroker;
use \Zend_Controller_Router_Route;
use \Zend_Controller_Front;
use \Icinga\Application\Logger;
use \Icinga\Authentication\Manager as AuthenticationManager;
use \Icinga\Exception\ConfigurationError;
use \Icinga\User\Preferences;
use \Icinga\User\Preferences\LoadInterface;
use \Icinga\User;
use \Icinga\Web\Request;
use \Icinga\Web\View;
use \Icinga\User\Preferences\StoreFactory;
use \Icinga\User\Preferences\SessionStore;
use \Icinga\Util\DateTimeFactory;

/**
 * Use this if you want to make use of Icinga functionality in other web projects
 *
 * Usage example:
 * <code>
 * use Icinga\Application\EmbeddedWeb;
 * EmbeddedWeb::start();
 * </code>
 */
class Web extends ApplicationBootstrap
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
     * Request object
     *
     * @var Request
     */
    private $request;

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
     * @return self
     */
    protected function bootstrap()
    {
        return $this->setupConfig()
            ->setupErrorHandling()
            ->setupResourceFactory()
            ->setupUser()
            ->setupTimezone()
            ->setupRequest()
            ->setupZendMvc()
            ->setupTranslation()
            ->setupModules()
            ->setupRoute()
            ->setupPagination();
    }

    /**
     * Prepare routing
     *
     * @return self
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

    /**
     * Load translations
     *
     * @return self
     */
    private function setupTranslation()
    {
        // AuthManager::getInstance()->getSession()->language;
        $locale = null;
        if (!$locale) {
            $locale = 'en_US';
        }
        putenv('LC_ALL=' . $locale . '.UTF-8');
        setlocale(LC_ALL, $locale . '.UTF-8');
        bindtextdomain('icinga', $this->getApplicationDir() . '/locale');
        textdomain('icinga');
        return $this;
    }

    /**
     * Dispatch public interface
     */
    public function dispatch()
    {
        $this->frontController->dispatch();
    }

    /**
     * Prepare Zend MVC Base
     *
     * @return self
     */
    private function setupZendMvc()
    {
        // TODO: Replace Zend_Application:
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
     * Registers a NullStore as the preference provider
     *
     * @param Preferences   $preferences    The preference registry to attach the NullStore to
     * @param User          $user           The user, required for API compliance
     *
     * @see   NullStore
     */
    private function registerFallbackPreferenceProvider($preferences, $user)
    {
        $this->getConfig()->preferences->type = 'null';
        $preferenceStore = StoreFactory::create(
            $this->getConfig()->preferences,
            $user
        );

        $preferences->attach($preferenceStore);
    }

    /**
     * Create user object and inject preference interface
     *
     * @return  self
     * @throws  ConfigurationError
     */
    private function setupUser()
    {
        $authenticationManager = AuthenticationManager::getInstance(
            null,
            array(
                'writeSession' => true
            )
        );

        if ($authenticationManager->isAuthenticated() === true) {
            $user = $authenticationManager->getUser();

            // Needed to update values in user session
            $sessionStore = new SessionStore($authenticationManager->getSession());

            // Performance: Do not ask provider if we've preferences
            // stored in session
            $initialPreferences = array();
            $preferencesLoaded = false;
            if (count($sessionStore->load())) {
                $initialPreferences = $sessionStore->load();
                $preferencesLoaded = true;
            }

            $preferences = new Preferences($initialPreferences);

            $preferences->attach($sessionStore);

            if ($this->getConfig()->preferences !== null) {
                if (!$this->getConfig()->preferences->type) {
                    Logger::info(
                        'Preferences provider configuration error. No type was omitted. For convenience we enable '
                        . 'file based ini provider for you.'
                    );

                    $this->getConfig()->preferences->type = 'ini';
                }

                $path = Config::resolvePath($this->getConfig()->preferences->configPath);
                if (is_dir($path) === false) {
                    Logger::warn(
                        'Path for preferences not found (IniStore, "%s"). Using default one: "%s"',
                        $this->getConfig()->preferences->configPath,
                        $this->getConfigDir('preferences')
                    );

                    $this->getConfig()->preferences->configPath = $this->getConfigDir('preferences');
                }

                $preferenceStore = null;

                try {
                    $preferenceStore = StoreFactory::create(
                        $this->getConfig()->preferences,
                        $user
                    );
                    $preferences->attach($preferenceStore);
                } catch (Exception $e) {
                    Logger::warn(
                        'Could not create create preferences provider, preferences will be discarded: '
                        . '"%s"',
                        $e->getMessage()
                    );
                    $this->registerFallbackPreferenceProvider($preferences, $user);
                }

                if ($preferencesLoaded === false && $preferenceStore instanceof LoadInterface) {
                    try {
                        $initialPreferences = $preferenceStore->load();
                    } catch (Exception $e) {
                        Logger::warn(
                            '%s::%s: Could not load preferences from provider. '
                            . 'An exception during bootstrap was thrown: %s',
                            __CLASS__,
                            __FUNCTION__,
                            $e->getMessage()
                        );
                        $this->registerFallbackPreferenceProvider($preferences, $user);
                    }

                    $sessionStore->writeAll($initialPreferences);
                }
            } else {
                Logger::error(
                    'Preferences are not configured. Refer to the documentation to setup a valid provider. '
                    . 'We will use session store only. Preferences are not persisted after logout'
                );
            }

            $user->setPreferences($preferences);

            $this->user = $user;
        }

        return $this;
    }

    /**
     * Inject dependencies into request
     *
     * @return self
     */
    private function setupRequest()
    {
        $this->request = new Request();

        if ($this->user instanceof User) {
            $this->request->setUser($this->user);
        }

        return $this;
    }

    /**
     * Instantiate front controller
     *
     * @return self
     */
    private function setupFrontController()
    {
        $this->frontController = Zend_Controller_Front::getInstance();

        $this->frontController->setRequest($this->request);

        $this->frontController->setControllerDirectory($this->getApplicationDir('/controllers'));

        $this->frontController->setParams(
            array(
                'displayExceptions' => true
            )
        );

        return $this;
    }

    /**
     * Register helper paths and views for renderer
     *
     * @return self
     */
    private function setupViewRenderer()
    {
        /** @var \Zend_Controller_Action_Helper_ViewRenderer $view */
        $view = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $view->setView(new View());

        $view->view->addHelperPath($this->getApplicationDir('/views/helpers'));

        $view->view->setEncoding('UTF-8');
        $view->view->headTitle()->prepend(
            $this->getConfig()->{'global'}->get('project', 'Icinga')
        );

        $view->view->headTitle()->setSeparator(' :: ');
        $view->view->navigation = $this->getConfig()->app('menu');

        $this->viewRenderer = $view;

        return $this;
    }

    /**
     * Configure pagination settings
     *
     * @return self
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
     * Setup user timezone if set and valid, otherwise global default timezone
     *
     * @return  self
     * @see     ApplicationBootstrap::setupTimezone
     */
    protected function setupTimezone()
    {
        $userTimeZone = $this->user === null ? null : $this->user->getPreferences()->get('app.timezone');

        try {
            $tz = new DateTimeZone($userTimeZone);
        } catch (Exception $e) {
            return parent::setupTimezone();
        }

        date_default_timezone_set($userTimeZone);
        DateTimeFactory::setConfig(array('timezone' => $tz));
        return $this;
    }
}
