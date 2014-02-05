<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

use Icinga\Module\Doc\DocParser;
use Icinga\Web\Controller\ActionController;
use Icinga\Web\Menu;

class Controller extends ActionController
{
    /**
     * Set HTML and toc
     *
     * @param string $dir
     */
    protected function populateViewFromDocDirectory($dir)
    {
        if (!@is_dir($dir)) {
            $this->view->html = null;
        } else {
            $parser             = new DocParser();
            list($html, $toc)   = $parser->parseDirectory($dir);
            $this->view->html   = $html;
            $this->view->toc    = $toc;
        }
    }
}