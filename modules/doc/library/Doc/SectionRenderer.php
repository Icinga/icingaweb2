<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc;

require_once 'Parsedown/Parsedown.php';

use DOMDocument;
use DOMXPath;
use Parsedown;
use RecursiveIteratorIterator;
use Icinga\Data\Tree\SimpleTree;
use Icinga\Module\Doc\Exception\ChapterNotFoundException;
use Icinga\Web\Url;
use Icinga\Web\View;

/**
 * Section renderer
 */
class SectionRenderer extends Renderer
{
    /**
     * Content to render
     *
     * @type array
     */
    protected $content = array();

    /**
     * Parsedown instance
     *
     * @type Parsedown
     */
    protected $parsedown;

    /**
     * Documentation tree
     *
     * @type SimpleTree
     */
    protected $tree;

    /**
     * Create a new section renderer
     *
     * @param   SimpleTree  $tree           The documentation tree
     * @param   string|null $chapter        If not null, the chapter to filter for
     *
     * @throws  ChapterNotFoundException    If the chapter to filter for was not found
     */
    public function __construct(SimpleTree $tree, $chapter = null)
    {
        if ($chapter !== null) {
            $filter = new SectionFilterIterator($tree->getIterator(), $chapter);
            if ($filter->isEmpty()) {
                throw new ChapterNotFoundException(
                    mt('doc', 'Chapter %s not found'), $chapter
                );
            }
            parent::__construct(
                $filter,
                RecursiveIteratorIterator::SELF_FIRST
            );
        } else {
            parent::__construct($tree->getIterator(), RecursiveIteratorIterator::SELF_FIRST);
        }
        $this->tree = $tree;
        $this->parsedown = Parsedown::instance();
    }

    /**
     * Syntax highlighting for PHP code
     *
     * @param   array $match
     *
     * @return  string
     */
    protected function highlightPhp($match)
    {
        return '<pre>' . highlight_string(htmlspecialchars_decode($match[1]), true) . '</pre>';
    }

    /**
     * Markup notes
     *
     * @param   array $match
     *
     * @return  string
     */
    protected function markupNotes($match)
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

    /**
     * Replace link
     *
     * @param   array $match
     *
     * @return  string
     */
    protected function replaceLink($match)
    {
        if (($section = $this->tree->getNode($this->decodeAnchor($match['fragment']))) === null) {
            return $match[0];
        }
        /** @type $section \Icinga\Module\Doc\DocSection */
        $path = $this->getView()->getHelper('Url')->url(
            array_merge(
                $this->urlParams,
                array(
                    'chapter' => $this->encodeUrlParam($section->getChapter()->getId())
                )
            ),
            $this->url,
            false,
            false
        );
        $url = $this->getView()->url($path);
        /** @type \Icinga\Web\Url $url */
        $url->setAnchor($this->encodeAnchor($section->getId()));
        return sprintf(
            '<a %s%shref="%s"',
            strlen($match['attribs']) ? trim($match['attribs']) . ' ' : '',
            $section->getNoFollow() ? 'rel="nofollow" ' : '',
            $url->getAbsoluteUrl()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        foreach ($this as $section) {
            $this->content[] = sprintf(
                '<a name="%1$s"></a><h%2$d>%3$s</h%2$d>',
                Renderer::encodeAnchor($section->getId()),
                $section->getLevel(),
                $this->getView()->escape($section->getTitle())
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
                array($this, 'markupNotes'),
                $html
            );
            $this->content[] = preg_replace_callback(
                '/<a\s+(?P<attribs>[^>]*?\s+)?href="(?:(?!http:\/\/)[^#]*)#(?P<fragment>[^"]+)"/',
                array($this, 'replaceLink'),
                $html
            );
        }
        return implode("\n", $this->content);
    }
}
