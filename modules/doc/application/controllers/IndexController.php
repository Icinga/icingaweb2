<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Controllers;

use Icinga\Module\Doc\DocController;
use Icinga\Web\Url;

/**
 * Documentation module index
 */
class IndexController extends DocController
{
    /**
     * Documentation module landing page
     *
     * Lists documentation links
     */
    public function indexAction()
    {
        $this->getTabs()->add('documentation', array(
            'active'    => true,
            'title'     => $this->translate('Documentation', 'Tab title'),
            'url'       => Url::fromRequest()
        ));
    }
}
