<?php
// @codingStandardsIgnoreStart
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


# namespace Icinga\Application\Controllers;

use Icinga\Web\ActionController;
use Icinga\Application\Icinga;
use Icinga\Web\Hook\Configuration\ConfigurationTabBuilder;
use Icinga\Application\Modules\Manager as ModuleManager;
use Zend_Controller_Action as ZfActionController;

/**
 * Handle module depending frontend actions
 */
class ModulesController extends ActionController
{
    /**
     * @var ModuleManager
     */
    protected $manager;

    /**
     * Setup this controller
     * @see ZfActionController::init
     */
    public function init()
    {
        $this->manager = Icinga::app()->getModuleManager();
    }

    /**
     * Display a list of all modules
     */
    public function indexAction()
    {
        $tabBuilder = new ConfigurationTabBuilder(
            $this->widget('tabs')
        );

        $tabBuilder->build();

        $this->view->tabs = $tabBuilder->getTabs();

        $this->view->modules = $this->manager->select()
            ->from('modules')
            ->order('name');
        $this->render('overview');
    }

    /**
     * Alias for index
     *
     * @see self::indexAction
     */
    public function overviewAction()
    {
        $this->indexAction();

    }

    /**
     * Enable a module
     */
    public function enableAction()
    {
        $this->manager->enableModule($this->_getParam('name'));
        $this->manager->loadModule($this->_getParam('name'));
        $this->getResponse()->setHeader('X-Icinga-Enable-Module', $this->_getParam('name'));
        $this->redirectNow('modules/overview?_render=body');

    }

    /**
     * Disable a module
     */
    public function disableAction()
    {
        $this->manager->disableModule($this->_getParam('name'));
        $this->redirectNow('modules/overview?_render=body');
    }
}

// @codingStandardsIgnoreEnd
