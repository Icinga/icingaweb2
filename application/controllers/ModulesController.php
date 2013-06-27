<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
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
