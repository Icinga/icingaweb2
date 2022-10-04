<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Controllers;

use Icinga\Web\Controller\ActionController;
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
            $landingPage = getenv('ICINGAWEB_LANDING_PAGE');
            if (! $landingPage) {
                $landingPage = 'dashboard';
            }

            // @TODO(el): Avoid landing page redirects: https://dev.icinga.com/issues/9656
            $this->redirectNow(Url::fromRequest()->setPath($landingPage));
        }
    }

    /**
     * Application's start page
     */
    public function welcomeAction()
    {
    }
}
