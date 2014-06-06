<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Doc;

use Icinga\Data\Tree\NodeRenderer;
use Icinga\Web\Controller\ActionController;

class DocController extends ActionController
{
    /**
     * Populate a chapter
     *
     * @param string $chapterName   Name of the chapter
     * @param string $path          Path to the documentation
     */
    protected function populateChapter($chapterName, $path)
    {
        $parser = new DocParser($path);
        $this->view->chapterHtml = $parser->getChapter($chapterName);
        $this->view->toc = $parser->getToc();
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
        $toc = $parser->getToc();
        $this->view->tocRenderer = new NodeRenderer($toc);
        $this->view->docName = $name;
    }
}
