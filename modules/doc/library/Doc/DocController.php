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
     * Populate toc
     *
     * @param string $path Path to the documentation
     * @param string $name Name of the documentation
     */
    protected function populateToc($path, $name)
    {
        $parser = new DocParser($path);
        list($docHtml, $tocRenderer) = $parser->getDocAndToc();
        $this->view->tocRenderer = $tocRenderer;
        $this->view->docName = $name;
    }
}
