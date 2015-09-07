<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Application\Config;
use Icinga\Web\Controller;
use Icinga\Web\Form;
use Icinga\Web\Url;

/**
 * Navigation configuration
 */
class NavigationController extends Controller
{
    /**
     * Show the current user a list of his/her navigation items
     */
    public function indexAction()
    {
        $user = $this->Auth()->getUser();
        $userConfig = $user->loadNavigationConfig();
        $sharedConfig = Config::app('navigation');

        $this->view->items = array_merge(
            $sharedConfig->select()->where('owner', $user->getUsername())->fetchAll(),
            iterator_to_array($userConfig)
        );

        $this->getTabs()->add(
            'navigation',
            array(
                'title'     => $this->translate('List and configure your own navigation items'),
                'label'     => $this->translate('Navigation'),
                'url'       => 'navigation'
            )
        )->activate('navigation');
    }

    /**
     * List all shared navigation items
     */
    public function sharedAction()
    {
        $this->assertPermission('config/application/navigation');
        $this->view->items = Config::app('navigation');

        $removeForm = new Form();
        $removeForm->setUidDisabled();
        $removeForm->setAction(Url::fromPath('navigation/unshare'));
        $removeForm->addElement('hidden', 'name', array(
            'decorators'    => array('ViewHelper')
        ));
        $removeForm->addElement('hidden', 'redirect', array(
            'value'         => Url::fromPath('navigation/shared'),
            'decorators'    => array('ViewHelper')
        ));
        $removeForm->addElement('button', 'btn_submit', array(
            'escape'        => false,
            'type'          => 'submit',
            'class'         => 'link-like spinner',
            'value'         => 'btn_submit',
            'decorators'    => array('ViewHelper'),
            'label'         => $this->view->icon('trash'),
            'title'         => $this->translate('Unshare this navigation item')
        ));
        $this->view->removeForm = $removeForm;

        $this->getTabs()->add(
            'navigation/shared',
            array(
                'title'     => $this->translate('List and configure shared navigation items'),
                'label'     => $this->translate('Shared Navigation'),
                'url'       => 'navigation/shared'
            )
        )->activate('navigation/shared');
    }

    /**
     * Add a navigation item
     */
    public function addAction()
    {
        
    }

    /**
     * Edit a navigation item
     */
    public function editAction()
    {
        
    }

    /**
     * Remove a navigation item
     */
    public function removeAction()
    {
        
    }
}
