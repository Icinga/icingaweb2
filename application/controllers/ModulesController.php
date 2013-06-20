<?php
// @codingStandardsIgnoreStart
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

# namespace Icinga\Application\Controllers;


use Icinga\Web\ActionController;
use Icinga\Application\Icinga;

class ModulesController extends ActionController
{
    protected $manager;

    public function init()
    {
        $this->manager = Icinga::app()->moduleManager();
    }

    public function indexAction()
    {
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
        $this->redirectNow('modules/overview?_render=body');
    }

    public function disableAction()
    {
        $this->manager->disableModule($this->_getParam('name'));
        $this->redirectNow('modules/overview?_render=body');
    }

}
