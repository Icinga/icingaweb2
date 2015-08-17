<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

# namespace Icinga\Application\Controllers;

use Icinga\Web\Controller\ActionController;
use Icinga\Application\Benchmark;
use Icinga\Web\Url;

/**
 * Application wide index controller
 */
class IndexController extends ActionController
{
    /**
     * Use a default redirection rule to welcome page
     */
    public function preDispatch()
    {
        if ($this->getRequest()->getActionName() !== 'welcome') {
            $this->redirectNow(Url::fromRequest()->setPath('dashboard'));
        }
    }

    /**
     * Application's start page
     */
    public function welcomeAction()
    {
    }
}
