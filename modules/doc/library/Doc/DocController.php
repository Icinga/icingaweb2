<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc;

use Icinga\Web\Controller\ModuleActionController;

class DocController extends ModuleActionController
{
    /**
     * Render a chapter
     *
     * @param string    $path       Path to the documentation
     * @param string    $chapter    ID of the chapter
     * @param string    $url        URL to replace links with
     * @param array     $urlParams  Additional URL parameters
     */
    protected function renderChapter($path, $chapter, $url, array $urlParams = array())
    {
        $parser = new DocParser($path);
        $section = new DocSectionRenderer($parser->getDocTree(), DocSectionRenderer::decodeUrlParam($chapter));
        $this->view->section = $section
            ->setUrl($url)
            ->setUrlParams($urlParams)
            ->setHighlightSearch($this->params->get('highlight-search'));
        $this->view->title = $chapter;
        $this->render('chapter', null, true);
    }

    /**
     * Render a toc
     *
     * @param string    $path       Path to the documentation
     * @param string    $name       Name of the documentation
     * @param string    $url        URL to replace links with
     * @param array     $urlParams  Additional URL parameters
     */
    protected function renderToc($path, $name, $url, array $urlParams = array())
    {
        $parser = new DocParser($path);
        $toc = new TocRenderer($parser->getDocTree()->getIterator());
        $this->view->toc = $toc
            ->setUrl($url)
            ->setUrlParams($urlParams);
        $name = ucfirst($name);
        $this->view->docName = $name;
        $this->view->title = sprintf($this->translate('%s Documentation'), $name);
        $this->render('toc', null, true);
    }

    /**
     * Render a pdf
     *
     * @param string    $path           Path to the documentation
     * @param string    $name           Name of the documentation
     * @param string    $url
     * @param array     $urlParams
     */
    protected function renderPdf($path, $name, $url, array $urlParams = array())
    {
        $parser = new DocParser($path);
        $docTree = $parser->getDocTree();
        $this->view->tocRenderer = new TocRenderer($docTree, $url, $urlParams);
        $this->view->sectionRenderer = new SectionRenderer(
            $docTree,
            null,
            null,
            $url,
            $urlParams
        );
        $this->view->docName = $name;
        $this->_request->setParam('format', 'pdf');
        $this->render('pdf', null, true);
    }
}
