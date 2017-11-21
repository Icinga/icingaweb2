<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Renderer;

require_once 'Parsedown/Parsedown.php';

use DOMDocument;
use DOMXPath;
use Parsedown;
use RecursiveIteratorIterator;
use Icinga\Data\Tree\SimpleTree;
use Icinga\Module\Doc\Exception\ChapterNotFoundException;
use Icinga\Module\Doc\DocSectionFilterIterator;
use Icinga\Module\Doc\Search\DocSearch;
use Icinga\Module\Doc\Search\DocSearchMatch;
use Icinga\Web\Dom\DomNodeIterator;
use Icinga\Web\Url;
use Icinga\Web\View;

/**
 * Section renderer
 */
class DocSectionRenderer extends DocRenderer
{
    /**
     * Content to render
     *
     * @var array
     */
    protected $content = array();

    /**
     * Search criteria to highlight
     *
     * @var string
     */
    protected $highlightSearch;

    /**
     * Parsedown instance
     *
     * @var Parsedown
     */
    protected $parsedown;

    /**
     * Documentation tree
     *
     * @var SimpleTree
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
            $filter = new DocSectionFilterIterator($tree->getIterator(), $chapter);
            if ($filter->isEmpty()) {
                throw new ChapterNotFoundException(
                    mt('doc', 'Chapter %s not found'),
                    $chapter
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
     * Set the search criteria to highlight
     *
     * @param   string $highlightSearch
     *
     * @return  $this
     */
    public function setHighlightSearch($highlightSearch)
    {
        $this->highlightSearch = $highlightSearch;
        return $this;
    }

    /**
     * Get the search criteria to highlight
     *
     * @return string
     */
    public function getHighlightSearch()
    {
        return $this->highlightSearch;
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
     * Highlight search criteria
     *
     * @param   string      $html
     * @param   DocSearch   $search Search criteria
     *
     * @return  string
     */
    protected function highlightSearch($html, DocSearch $search)
    {
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $iter = new RecursiveIteratorIterator(new DomNodeIterator($doc), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($iter as $node) {
            if ($node->nodeType !== XML_TEXT_NODE
                || ($node->parentNode->nodeType === XML_ELEMENT_NODE && $node->parentNode->tagName === 'code')
            ) {
                continue;
            }
            $text = $node->nodeValue;
            if (($match = $search->search($text)) === null) {
                continue;
            }
            $matches = $match->getMatches();
            ksort($matches);
            $offset = 0;
            $fragment = $doc->createDocumentFragment();
            foreach ($matches as $position => $match) {
                    $fragment->appendChild($doc->createTextNode(substr($text, $offset, $position - $offset)));
                    $fragment->appendChild($doc->createElement('span', $match))
                        ->setAttribute('class', DocSearchMatch::HIGHLIGHT_CSS_CLASS);
                $offset = $position + strlen($match);
            }
            $fragment->appendChild($doc->createTextNode(substr($text, $offset)));
            $node->parentNode->replaceChild($fragment, $node);
        }
        // Remove <!DOCTYPE
        $doc->removeChild($doc->doctype);
        // Remove <html><body> and </body></html>
        return substr($doc->saveHTML(), 12, -15);
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
        /** @var \DOMElement $blockquote */
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
        /** @var \DOMElement $img */
        $path = $this->getView()->getHelper('Url')->url(
            array_merge(
                array(
                    'image' => trim($img->getAttribute('src'))
                ),
                $this->urlParams
            ),
            $this->imageUrl,
            false,
            false
        );
        $url = $this->getView()->url($path);
        /** @var \Icinga\Web\Url $url */
        $img->setAttribute('src', $url->getAbsoluteUrl());
        return substr_replace($doc->saveXML($img), '', -2, 1);  // Replace '/>' with '>'
    }

    /**
     * Replace chapter link
     *
     * @param   array $match
     *
     * @return  string
     */
    protected function replaceChapterLink($match)
    {
        if (($chapter = $this->tree->getNode($this->decodeAnchor($match['chapter']))) === null) {
            return $match[0];
        }
        /** @var \Icinga\Module\Doc\DocSection $section */
        $path = $this->getView()->getHelper('Url')->url(
            array_merge(
                $this->urlParams,
                array(
                    'chapter' => $this->encodeUrlParam($chapter->getChapter()->getId())
                )
            ),
            $this->url,
            false,
            false
        );
        $url = $this->getView()->url($path);
        /** @var \Icinga\Web\Url $url */
        return sprintf(
            '<a %s%shref="%s"',
            strlen($match['attribs']) ? trim($match['attribs']) . ' ' : '',
            $chapter->getNoFollow() ? 'rel="nofollow" ' : '',
            $url->getAbsoluteUrl()
        );
    }

    /**
     * Replace section link
     *
     * @param   array $match
     *
     * @return  string
     */
    protected function replaceSectionLink($match)
    {
        if (($section = $this->tree->getNode($this->decodeAnchor($match['section']))) === null) {
            return $match[0];
        }
        /** @var \Icinga\Module\Doc\DocSection $section */
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
        /** @var \Icinga\Web\Url $url */
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
        $search = null;
        if (($highlightSearch = $this->getHighlightSearch()) !== null) {
            $search = new DocSearch($highlightSearch);
        }
        foreach ($this as $section) {
            $title = $section->getTitle();
            if ($search !== null && ($match = $search->search($title)) !== null) {
                $title = $match->highlight();
            } else {
                $title = $this->getView()->escape($title);
            }
            $number = '';
            for ($i = 0; $i < $this->getDepth() + 1; ++$i) {
                if ($i > 0) {
                    $number .= '.';
                }
                $number .= $this->getSubIterator($i)->key() + 1;
            }
            $this->content[] = sprintf(
                '<a name="%1$s"></a><h%2$d>%3$s. %4$s</h%2$d>',
                static::encodeAnchor($section->getId()),
                $section->getLevel(),
                $number,
                $title
            );
            $html = $this->parsedown->text(implode('', $section->getContent()));
            if (empty($html)) {
                continue;
            }
            $html = preg_replace_callback(
                '#<pre><code class="language-php">(.*?)</code></pre>#s',
                array($this, 'highlightPhp'),
                $html
            );
            $html = preg_replace_callback(
                '/<img[^>]+>/',
                array($this, 'replaceImg'),
                $html
            );
            $html = preg_replace_callback(
                '#<blockquote>.+?</blockquote>#ms',
                array($this, 'markupNotes'),
                $html
            );
            $html = preg_replace_callback(
                '/<a\s+(?P<attribs>[^>]*?\s+)?href="(?:(?!http:\/\/)[^"#]*)#(?P<section>[^"]+)"/',
                array($this, 'replaceSectionLink'),
                $html
            );
            $html = preg_replace_callback(
                '/<a\s+(?P<attribs>[^>]*?\s+)?href="(?:\d+-)?(?P<chapter>[^\/"#]+).md"/',
                array($this, 'replaceChapterLink'),
                $html
            );
            if ($search !== null) {
                $html = $this->highlightSearch($html, $search);
            }
            $this->content[] = $html;
        }
        return implode("\n", $this->content);
    }
}
