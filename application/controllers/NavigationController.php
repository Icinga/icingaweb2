<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\Controller;

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
        
    }

    /**
     * List all shared navigation items
     */
    public function sharedAction()
    {
        $this->assertPermission('config/application/navigation');
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
