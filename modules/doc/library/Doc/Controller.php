<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

use Icinga\Web\Controller\ActionController;

class Controller extends ActionController
{
    /**
     * Publish doc HTML and toc to the view
     *
     * @param string $module Name of the module for which to populate doc and toc. `null` for Icinga Web 2's doc
     */
    protected function populateView($module = null)
    {
        $parser = new DocParser($module);
        list($docHtml, $docToc) = $parser->getDocAndToc();
        $this->view->docHtml = $docHtml;
        $this->view->docToc = $docToc;
    }
}
