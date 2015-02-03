<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Doc;

require_once 'Parsedown/Parsedown.php';

use DOMDocument;
use DOMXPath;
use RecursiveIteratorIterator;
use Parsedown;
use Zend_View_Helper_Url;
use Icinga\Module\Doc\Exception\ChapterNotFoundException;
use Icinga\Web\Url;
use Icinga\Web\View;

/**
 * preg_replace_callback helper to replace links
 */
class Callback
{
    protected $docTree;

    protected $view;

    protected $zendUrlHelper;

    protected $url;

    protected $urlParams;

    public function __construct(
        DocTree $docTree,
        View $view,
        Zend_View_Helper_Url $zendUrlHelper,
        $url,
        array $urlParams)
    {
        $this->docTree = $docTree;
        $this->view = $view;
        $this->zendUrlHelper = $zendUrlHelper;
        $this->url = $url;
        $this->urlParams = $urlParams;
    }

    public function render($match)
    {
        $node = $this->docTree->getNode(Renderer::decodeAnchor($match['fragment']));
        /* @var $node \Icinga\Data\Tree\Node */
        if ($node === null) {
            return $match[0];
        }
        $section = $node->getValue();
        /* @var $section \Icinga\Module\Doc\Section */
        $path = $this->zendUrlHelper->url(
            array_merge(
                $this->urlParams,
                array(
                    'chapterId' => SectionRenderer::encodeUrlParam($section->getChapterId())
                )
            ),
            $this->url,
            false,
            false
        );
        $url = $this->view->url($path);
        $url->setAnchor(SectionRenderer::encodeAnchor($section->getId()));
        return sprintf(
            '<a %s%shref="%s"',
            strlen($match['attribs']) ? trim($match['attribs']) . ' ' : '',
            $section->isNoFollow() ? 'rel="nofollow" ' : '',
            $url->getAbsoluteUrl()
        );
    }
}

/**
 * Section renderer
 */
class SectionRenderer extends Renderer
{
    /**
     * The documentation tree
     *
     * @var DocTree
     */
    protected $docTree;

    protected $tocUrl;

    /**
     * The URL to replace links with
     *
     * @var string
     */
    protected $url;

    /**
     * Additional URL parameters
     *
     * @var array
     */
    protected $urlParams;

    /**
     * Parsedown instance
     *
     * @var Parsedown
     */
    protected $parsedown;

    /**
     * Content
     *
     * @var array
     */
    protected $content = array();

    /**
     * Create a new section renderer
     *
     * @param   DocTree     $docTree        The documentation tree
     * @param   string|null $chapterId      If not null, the chapter ID to filter for
     * @param   string      $tocUrl
     * @param   string      $url            The URL to replace links with
     * @param   array       $urlParams      Additional URL parameters
     *
     * @throws  ChapterNotFoundException    If the chapter to filter for was not found
     */
    public function __construct(DocTree $docTree, $chapterId, $tocUrl, $url, array $urlParams)
    {
        if ($chapterId !== null) {
            $filter = new SectionFilterIterator($docTree, $chapterId);
            if ($filter->count() === 0) {
                throw new ChapterNotFoundException(
                    sprintf(mt('doc', 'Chapter \'%s\' not found'), $chapterId)
                );
            }
            parent::__construct(
                $filter,
                RecursiveIteratorIterator::SELF_FIRST
            );
        } else {
            parent::__construct($docTree, RecursiveIteratorIterator::SELF_FIRST);
        }
        $this->docTree = $docTree;
        $this->tocUrl = $tocUrl;
        $this->url = $url;
        $this->urlParams = array_map(array($this, 'encodeUrlParam'), $urlParams);
        $this->parsedown = Parsedown::instance();
    }

    /**
     * Syntax highlighting for PHP code
     *
     * @param   $match
     *
     * @return  string
     */
    protected function highlightPhp($match)
    {
        return '<pre>' . highlight_string(htmlspecialchars_decode($match[1]), true) . '</pre>';
    }

    /**
     * Replace img src tags
     *
     * @param   $match
     *
     * @return  string
     */
    protected function replaceImg($match)
    {
        $doc = new DOMDocument();
        $doc->loadHTML($match[0]);
        $xpath = new DOMXPath($doc);
        $img = $xpath->query('//img[1]')->item(0);
        /* @var $img \DOMElement */
        $img->setAttribute('src', Url::fromPath($img->getAttribute('src'))->getAbsoluteUrl());
        return substr_replace($doc->saveXML($img), '', -2, 1);  // Replace '/>' with '>'
    }

    protected function blubb($match)
    {
        $doc = new DOMDocument();
        $doc->loadHTML($match[0]);
        $xpath = new DOMXPath($doc);
        $blockquote = $xpath->query('//blockquote[1]')->item(0);
        /* @var $blockquote \DOMElement */
        if (strtolower(substr(trim($blockquote->nodeValue), 0, 5)) === 'note:') {
            $blockquote->setAttribute('class', 'note');
        }
        return $doc->saveXML($blockquote);
    }

    /**
     * Render the section
     *
     * @param   View                    $view
     * @param   Zend_View_Helper_Url    $zendUrlHelper
     * @param   bool                    $renderNavigation
     *
     * @return  string
     */
    public function render(View $view, Zend_View_Helper_Url $zendUrlHelper, $renderNavigation = true)
    {
        $callback = new Callback($this->docTree, $view, $zendUrlHelper, $this->url, $this->urlParams);
        $content = array();
        foreach ($this as $node) {
            $section = $node->getValue();
            /* @var $section \Icinga\Module\Doc\Section */
            $content[] = sprintf(
                '<a name="%1$s"></a><h%2$d>%3$s</h%2$d>',
                Renderer::encodeAnchor($section->getId()),
                $section->getLevel(),
                $view->escape($section->getTitle())
            );
            $html = preg_replace_callback(
                '#<pre><code class="language-php">(.*?)</code></pre>#s',
                array($this, 'highlightPhp'),
                $this->parsedown->text(implode('', $section->getContent()))
            );
            $html = preg_replace_callback(
                '/<img[^>]+>/',
                array($this, 'replaceImg'),
                $html
            );
            $html = preg_replace_callback(
                '#<blockquote>.+</blockquote>#ms',
                array($this, 'blubb'),
                $html
            );
            $content[] = preg_replace_callback(
                '/<a\s+(?P<attribs>[^>]*?\s+)?href="(?:(?!http:\/\/)[^#]*)#(?P<fragment>[^"]+)"/',
                array($callback, 'render'),
                $html
            );
        }
        if ($renderNavigation) {
            foreach ($this->docTree as $chapter) {
                if ($chapter->getValue()->getId() === $section->getChapterId()) {
                    $navigation = array('<ul class="navigation">');
                    $this->docTree->prev();
                    $prev = $this->docTree->current();
                    if ($prev !== null) {
                        $prev = $prev->getValue();
                        $path = $zendUrlHelper->url(
                            array_merge(
                                $this->urlParams,
                                array(
                                    'chapterId' => $this->encodeUrlParam($prev->getChapterId())
                                )
                            ),
                            $this->url,
                            false,
                            false
                        );
                        $url = $view->url($path);
                        $url->setAnchor($this->encodeAnchor($prev->getId()));
                        $navigation[] = sprintf(
                            '<li class="prev"><a %shref="%s">%s</a></li>',
                            $prev->isNoFollow() ? 'rel="nofollow" ' : '',
                            $url->getAbsoluteUrl(),
                            $view->escape($prev->getTitle())
                        );
                        $this->docTree->next();
                        $this->docTree->next();
                    } else {
                        $this->docTree->rewind();
                        $this->docTree->next();
                    }
                    $url = $view->url($this->tocUrl);
                    $navigation[] = sprintf(
                        '<li><a href="%s">%s</a></li>',
                        $url->getAbsoluteUrl(),
                        mt('doc', 'Index')
                    );
                    $next = $this->docTree->current();
                    if ($next !== null) {
                        $next = $next->getValue();
                        $path = $zendUrlHelper->url(
                            array_merge(
                                $this->urlParams,
                                array(
                                    'chapterId' => $this->encodeUrlParam($next->getChapterId())
                                )
                            ),
                            $this->url,
                            false,
                            false
                        );
                        $url = $view->url($path);
                        $url->setAnchor($this->encodeAnchor($next->getId()));
                        $navigation[] = sprintf(
                            '<li class="next"><a %shref="%s">%s</a></li>',
                            $next->isNoFollow() ? 'rel="nofollow" ' : '',
                            $url->getAbsoluteUrl(),
                            $view->escape($next->getTitle())
                        );
                    }
                    $navigation[] = '</ul>';
                    $content = array_merge($navigation, $content, $navigation);
                    break;
                }
            }
        }
        return implode("\n", $content);
    }
}
