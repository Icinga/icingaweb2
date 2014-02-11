<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

use Icinga\Web\Controller\ActionController;

class Controller extends ActionController
{
    /**
     * Set HTML and toc
     *
     * @param string $module
     */
    protected function populateView($module = null)
    {
        $parser             = new DocParser($module);
        list($html, $toc)   = $parser->getDocumentation();
        $this->view->html   = $html;
        $this->view->toc    = $toc;
    }
}