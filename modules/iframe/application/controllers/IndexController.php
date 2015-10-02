<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Iframe\Controllers;

use Icinga\Web\Controller;

/**
 * Display external or internal links within an iframe
 */
class IndexController extends Controller
{
    /**
     * Display iframe w/ the given URL
     */
    public function indexAction()
    {
        $this->view->url = $this->params->getRequired('url');
    }
}
