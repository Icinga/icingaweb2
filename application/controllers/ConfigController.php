<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * Icinga 2 Web - Head for multiple monitoring frontends
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
 * @author Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

use \Icinga\Application\Benchmark;
use \Icinga\Authentication\Manager;
use \Icinga\Web\Controller\BaseConfigController;
use \Icinga\Web\Widget\Tab;
use \Icinga\Web\Url;
use \Icinga\Web\Hook\Configuration\ConfigurationTabBuilder;
use \Icinga\Application\Icinga;

/**
 * Application wide controller for application preferences
 */
class ConfigController extends BaseConfigController
{
    /**
     * Create tabs for this configuration controller
     *
     * @return  array
     *
     * @see     BaseConfigController::createProvidedTabs()
     */
    public static function createProvidedTabs()
    {
        return array(
            'index' => new Tab(
                array(
                    'name'      => 'index',
                    'title'     => 'Configuration',
                    'iconCls'   => 'wrench',
                    'url'       => Url::fromPath('/config')
                )
            ),
            'modules' => new Tab(
                array(
                    'name'      => 'modules',
                    'title'     => 'Modules',
                    'iconCls'   => 'puzzle-piece',
                    'url'       => Url::fromPath('/config/moduleoverview')
                )
            )
        );
    }

    /**
     * Index action, entry point for configuration
     * @TODO: Implement configuration interface (#3777)
     */
    public function indexAction()
    {

    }

    /**
     * Display the list of all modules
     */
    public function moduleoverviewAction()
    {
        $this->view->modules = Icinga::app()->getModuleManager()->select()
            ->from('modules')
            ->order('name');
        $this->render('module/overview');
    }

    /**
     * Enable a specific module provided by the 'name' param
     */
    public function moduleenableAction()
    {
        $manager = Icinga::app()->getModuleManager();
        $manager->enableModule($this->_getParam('name'));
        $manager->loadModule($this->_getParam('name'));
        $this->redirectNow('config/moduleoverview?_render=body');
    }

    /**
     * Disable a module specific module provided by the 'name' param
     */
    public function moduledisableAction()
    {
        $manager = Icinga::app()->getModuleManager();
        $manager->disableModule($this->_getParam('name'));
        $this->redirectNow('config/moduleoverview?_render=body');
    }
}
// @codingStandardsIgnoreEnd
