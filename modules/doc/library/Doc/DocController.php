<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

use Icinga\Web\Controller\ActionController;

class DocController extends ActionController
{
    /**
     * Publish doc HTML and toc to the view
     *
     * @param string $module Name of the module for which to populate doc and toc. `null` for Icinga Web 2's doc
     */
    protected function renderDocAndToc($module = null)
    {
        $parser = new DocParser($module);
        list($docHtml, $docToc) = $parser->getDocAndToc();
        $this->view->docHtml = $docHtml;
        $this->view->docToc = $docToc;
        $this->view->docName = $module === null ? 'Icinga Web 2' : ucfirst($module);
        $this->_helper->viewRenderer('partials/docandtoc', null, true);
    }

    /**
     * Render a toc
     *
     * @param string $path Path to the documentation
     * @param string
     */
    protected function renderToc($path, $name)
    {
        $parser = new DocParser($path);
        list($docHtml, $docToc) = $parser->getDocAndToc();
        $this->view->docToc = $docToc;
        $this->view->docName = $name;
        $this->_helper->viewRenderer('partials/toc', null, true);
    }
}
