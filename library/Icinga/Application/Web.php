<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application;

use Icinga\Authentication\Manager;
use Zend_Controller_Front as FrontController;
use Zend_Layout as Layout;
use Zend_Paginator as Paginator;
use Zend_View_Helper_PaginationControl as PaginationControl;
use Zend_Controller_Action_HelperBroker as ActionHelper;
use Zend_Controller_Router_Route as Route;

/**
 * Use this if you want to make use of Icinga funtionality in other web projects
 *
 * Usage example:
 * <code>
 * use Icinga\Application\EmbeddedWeb;
 * EmbeddedWeb::start();
 * </code>
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @package    Icinga\Application
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Web extends ApplicationBootstrap
{
    protected $view;
    protected $frontController;
    protected $isWeb = true;

    protected function bootstrap()
    {
        return $this->loadConfig()
            ->configureErrorHandling()
            ->setTimezone()
            ->configureCache()
            ->prepareZendMvc()
            ->loadTranslations()
            ->loadEnabledModules()
            ->setupSpecialRoutes()
            ->configurePagination();
    }

    protected function setupSpecialRoutes()
    {
        // TODO: Find a better solution
        $this->frontController->getRouter()->addRoute(
            'module_overview',
            new Route(
                'js/modules/list.js',
                array(
                    'controller' => 'static',
                    'action' => 'modulelist',
                )
            )
        );

        $this->frontController->getRouter()->addRoute(
            'module_javascript',
            new Route(
                'js/modules/:module_name/:file',
                array(
                    'controller' => 'static',
                    'action' => 'javascript'
                )
            )
        );
        return $this;
    }

    public function frontController()
    {
        // TODO: ProgrammingError if null
        return $this->frontController;
    }

    public function getView()
    {
        // TODO: ProgrammingError if null
        return $this->view;
    }

    public function dispatch()
    {
        $this->dispatchFrontController();
    }


    protected function loadTranslations()
    {
        // AuthManager::getInstance()->getSession()->language;
        $locale = null;
        if (!$locale) {
            $locale = 'en_US';
        }
        putenv('LC_ALL=' . $locale . '.UTF-8');
        setlocale(LC_ALL, $locale . '.UTF-8');
        bindtextdomain('icinga', ICINGA_APPDIR . '/locale');
        textdomain('icinga');
        return $this;
    }

    protected function dispatchFrontController()
    {
        // AuthManager::getInstance()->getSession();
        $this->frontController->dispatch();
        return $this;
    }

    /**
     * Prepare Zend MVC Base
     *
     * @return self
     */
    protected function prepareZendMvc()
    {
        // TODO: Replace Zend_Application:
        Layout::startMvc(
            array(
                'layout' => 'layout',
                'layoutPath' => $this->appdir . '/layouts/scripts'
            )
        );

        return $this->prepareFrontController()
            ->prepareView();
    }

    protected function prepareFrontController()
    {
        $this->frontController = FrontController::getInstance();

        $this->frontController->setControllerDirectory($this->appdir . '/controllers');

        $this->frontController->setParams(
            array(
                'displayExceptions' => true
            )
        );

        return $this;
    }

    protected function prepareView()
    {
        $view = ActionHelper::getStaticHelper('viewRenderer');
        $view->initView();

        $view->view->addHelperPath($this->appdir . '/views/helpers');
        // TODO: find out how to avoid this additional helper path:
        $view->view->addHelperPath($this->appdir . '/views/helpers/layout');

        $view->view->setEncoding('UTF-8');
        $view->view->headTitle()->prepend(
            $this->config->{'global'}->get('project', 'Icinga')
        );
        $view->view->headTitle()->setSeparator(' :: ');
        $view->view->navigation = $this->config->menu;

        $this->view = $view;
        return $this;
    }

    /**
     * Configure pagination settings
     *
     * @return self
     */
    protected function configurePagination()
    {

        Paginator::addScrollingStylePrefixPath(
            'Icinga_Web_Paginator_ScrollingStyle',
            'Icinga/Web/Paginator/ScrollingStyle'
        );

        Paginator::setDefaultScrollingStyle('SlidingWithBorder');
        PaginationControl::setDefaultViewPartial(
            array('mixedPagination.phtml', 'default')
        );
        return $this;
    }
}
