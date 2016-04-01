<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc;

use Icinga\Module\Doc\Renderer\DocSectionRenderer;
use Icinga\Module\Doc\Renderer\DocTocRenderer;
use Icinga\Web\Controller;
use Icinga\Web\Url;

class DocController extends Controller
{
    /**
     * {@inheritdoc}
     */
    protected function moduleInit()
    {
        // Our UrlParams object does not take parameters from custom routes into account which is why we have to set
        // them explicitly
        if ($this->hasParam('chapter')) {
            $this->params->set('chapter', $this->getParam('chapter'));
        }
        if ($this->hasParam('image')) {
            $this->params->set('image', $this->getParam('image'));
        }
        if ($this->hasParam('moduleName')) {
            $this->params->set('moduleName', $this->getParam('moduleName'));
        }
    }

    /**
     * Render a chapter
     *
     * @param string    $path       Path to the documentation
     * @param string    $chapter    ID of the chapter
     * @param string    $url        URL to replace links with
     * @param string    $imageUrl   URL to images
     * @param array     $urlParams  Additional URL parameters
     */
    protected function renderChapter($path, $chapter, $url, $imageUrl = null, array $urlParams = array())
    {
        $parser = new DocParser($path);
        $section = new DocSectionRenderer($parser->getDocTree(), DocSectionRenderer::decodeUrlParam($chapter));
        $this->view->section = $section
            ->setHighlightSearch($this->params->get('highlight-search'))
            ->setImageUrl($imageUrl)
            ->setUrl($url)
            ->setUrlParams($urlParams);
        $first = null;
        foreach ($section as $first) {
            break;
        }
        $title = $first === null ? ucfirst($chapter) : $first->getTitle();
        $this->view->title = $title;
        $this->getTabs()->add('toc', array(
            'active'    => true,
            'title'     => $title,
            'url'       => Url::fromRequest()
        ));
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
        $title = sprintf($this->translate('%s Documentation'), $name);
        $this->getTabs()->add('toc', array(
            'active'    => true,
            'title'     => $title,
            'url'       => Url::fromRequest()
        ));
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
