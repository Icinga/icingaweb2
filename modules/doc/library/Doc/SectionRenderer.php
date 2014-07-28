<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}

namespace Icinga\Module\Doc;

require_once 'IcingaVendor/Parsedown/Parsedown.php';

use Icinga\Module\Doc\Exception\ChapterNotFoundException;
use RecursiveIteratorIterator;
use Parsedown;
use Zend_View_Helper_Url;
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
                    'chapterName' => SectionRenderer::encodeUrlParam($section->getChapterTitle())
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
            $section->isNofollow() ? 'rel="nofollow" ' : '',
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
     * @param   string|null $chapterTitle   If not null, the chapter title to filter for
     * @param   string      $url            The URL to replace links with
     * @param   array       $urlParams      Additional URL parameters
     *
     * @throws  ChapterNotFoundException    If the chapter to filter for was not found
     */
    public function __construct(DocTree $docTree, $chapterTitle, $url, array $urlParams)
    {
        if ($chapterTitle !== null) {
            $filter = new SectionFilterIterator($docTree, $chapterTitle);
            if ($filter->count() === 0) {
                throw new ChapterNotFoundException(
                    mt('doc', 'Chapter') . ' \'' . $chapterTitle . '\' ' . mt('doc', 'not found')
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
     * Render the section
     *
     * @param   View                    $view
     * @param   Zend_View_Helper_Url    $zendUrlHelper
     * @return  string
     */
    public function render(View $view, Zend_View_Helper_Url $zendUrlHelper)
    {
        $callback = new Callback($this->docTree, $view, $zendUrlHelper, $this->url, $this->urlParams);
        $content = array();
        foreach ($this as $node) {
            $section = $node->getValue();
            /* @var $section \Icinga\Module\Doc\Section */
            $content[] = sprintf(
                '<a name="%1$s"></a> <h%2$d>%3$s</h%2$d>',
                Renderer::encodeAnchor($section->getId()),
                $section->getLevel(),
                $view->escape($section->getTitle())
            );
            $html = preg_replace_callback(
                '#<pre><code class="language-php">(.*?)</code></pre>#s',
                array($this, 'highlightPhp'),
                $this->parsedown->text(implode('', $section->getContent()))
            );
            $content[] = preg_replace_callback(
                '/<a\s+(?P<attribs>[^>]*?\s+)?href="#(?P<fragment>[^"]+)"/',
                array($callback, 'render'),
                $html
            );
        }
        return implode("\n", $content);
    }
}
