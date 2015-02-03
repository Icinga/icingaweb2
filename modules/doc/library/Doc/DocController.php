<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Doc;

use Icinga\Web\Controller\ModuleActionController;

class DocController extends ModuleActionController
{
    /**
     * Render a chapter
     *
     * @param string    $path           Path to the documentation
     * @param string    $chapterId      ID of the chapter
     * @param string    $tocUrl
     * @param string    $url
     * @param array     $urlParams
     */
    protected function renderChapter($path, $chapterId, $tocUrl, $url, array $urlParams = array())
    {
        $parser = new DocParser($path);
        $this->view->sectionRenderer = new SectionRenderer(
            $parser->getDocTree(),
            SectionRenderer::decodeUrlParam($chapterId),
            $tocUrl,
            $url,
            $urlParams
        );
        $this->view->title = $chapterId;
        $this->render('chapter', null, true);
    }

    /**
     * Render a toc
     *
     * @param string    $path           Path to the documentation
     * @param string    $name           Name of the documentation
     * @param string    $url
     * @param array     $urlParams
     */
    protected function renderToc($path, $name, $url, array $urlParams = array())
    {
        $parser = new DocParser($path);
        $this->view->tocRenderer = new TocRenderer($parser->getDocTree(), $url, $urlParams);
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
