<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc;

use Icinga\Module\Doc\Renderer\DocSectionRenderer;
use Icinga\Module\Doc\Renderer\DocTocRenderer;
use Icinga\Web\Controller;

class DocController extends Controller
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
        $toc = new DocTocRenderer($parser->getDocTree()->getIterator());
        $this->view->toc = $toc
            ->setUrl($url)
            ->setUrlParams($urlParams);
        $name = ucfirst($name);
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
        $toc = new DocTocRenderer($parser->getDocTree()->getIterator());
        $this->view->toc = $toc
            ->setUrl($url)
            ->setUrlParams($urlParams);
        $section = new DocSectionRenderer($parser->getDocTree());
        $this->view->section = $section
            ->setUrl($url)
            ->setUrlParams($urlParams);
        $this->view->title = sprintf($this->translate('%s Documentation'), $name);
        $this->_request->setParam('format', 'pdf');
        $this->_helper->viewRenderer->setRender('pdf', null, true);
    }
}
