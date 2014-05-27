<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

use Icinga\Web\Controller\ActionController;

class DocController extends ActionController
{
    /**
     * Render a chapter
     *
     * @param string $chapterName   Name of the chapter
     * @param string $path          Path to the documentation
     */
    protected function renderChapter($chapterName, $path)
    {
        $parser = new DocParser($path);
        list($docHtml, $docToc) = $parser->getDocAndToc();
        $this->view->chapterHtml = $docHtml;
        $this->_helper->viewRenderer('partials/chapter', null, true);
    }

    /**
     * Render a toc
     *
     * @param string $path Path to the documentation
     * @param string $name Name of the documentation
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
