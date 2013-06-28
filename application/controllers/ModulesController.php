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

# namespace Icinga\Application\Controllers;

use Icinga\Web\ActionController;
use Icinga\Application\Icinga;
use Icinga\Web\Hook\Configuration\ConfigurationTabBuilder;

class ModulesController extends ActionController
{
    protected $manager;

    public function init()
    {
        $this->manager = Icinga::app()->moduleManager();
    }

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

    public function overviewAction()
    {
        $this->indexAction();
        
    }

    public function enableAction()
    {
        $this->manager->enableModule($this->_getParam('name'));
        $this->manager->loadModule($this->_getParam('name'));
        $this->getResponse()->setHeader('X-Icinga-Enable-Module', $this->_getParam('name')); 
        $this->replaceLayout = true; 
        $this->indexAction();

    }

    public function disableAction()
    {
        $this->manager->disableModule($this->_getParam('name'));
        $this->redirectNow('modules/overview?_render=body');
    }

}
