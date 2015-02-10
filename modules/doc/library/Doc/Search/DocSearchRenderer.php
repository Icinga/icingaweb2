<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Search;

use RecursiveIteratorIterator;
use Icinga\Module\Doc\Renderer;

/**
 * Renderer for doc searches
 *
 * @method DocSearchIterator getInnerIterator() {
 *     @{inheritdoc}
 * }
 */
class DocSearchRenderer extends Renderer
{
    /**
     * CSS class
     *
     * @type string
     */
    const HIGHLIGHT_CSS_CLASS = 'search-highlight';

    /**
     * The content to render
     *
     * @type array
     */
    protected $content = array();

    /**
     * Create a new renderer for doc searches
     *
     * @param DocSearchIterator $iterator
     */
    public function __construct (DocSearchIterator $iterator)
    {
        parent::__construct($iterator, RecursiveIteratorIterator::SELF_FIRST);
    }

    /**
     * {@inheritdoc}
     */
    public function beginIteration()
    {
        $this->content[] = '<nav><ul>';
    }

    /**
     * {@inheritdoc}
     */
    public function endIteration()
    {
        $this->content[] = '</ul></nav>';
    }

    /**
     * {@inheritdoc}
     */
    public function beginChildren()
    {
        if ($this->getInnerIterator()->getMatches()) {
            $this->content[] = '<ul>';
        }
    }

    /**
     * {@inheritdoc}
     */
    public function endChildren()
    {
        if ($this->getInnerIterator()->getMatches()) {
            $this->content[] = '</ul>';
        }
    }

    public function highlight($line, array $matches)
    {
        $highlighted = '';
        $offset = 0;
        ksort($matches);
        foreach ($matches as $position => $match) {
            $highlighted .= $this->getView()->escape(substr($line, $offset, $position - $offset))
                . '<span class="' . static::HIGHLIGHT_CSS_CLASS .'">'
                . $this->getView()->escape($match)
                . '</span>';
            $offset = $position + strlen($match);
        }
        $highlighted .= $this->getView()->escape(substr($line, $offset));
        return $highlighted;
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        foreach ($this as $section) {
            if (($matches = $this->getInnerIterator()->getMatches()) === null) {
                continue;
            }
            $title = $this->getView()->escape($section->getTitle());
            $contentMatches = array();
            foreach ($matches as $match) {
                if ($match->getMatchType() === DocSearchMatch::MATCH_HEADER) {
                    $title = $this->highlight($match->getLine(), $match->getMatches());
                } else {
                    $contentMatches[] = sprintf(
                        '<p>%s</p>',
                        $this->highlight($match->getLine(), $match->getMatches())
                    );
                }
            }
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
            $url = $this->getView()->url(
                $path,
                array('highlight' => $this->getInnerIterator()->getSearch()->getInput())
            );
            /** @type \Icinga\Web\Url $url */
            $url->setAnchor($this->encodeAnchor($section->getId()));
            $this->content[] = sprintf(
                '<li><a data-base-target="_next" %shref="%s">%s</a>',
                $section->getNoFollow() ? 'rel="nofollow" ' : '',
                $url->getAbsoluteUrl(),
                $title
            );
            if (! empty($contentMatches)) {
                $this->content = array_merge($this->content, $contentMatches);
            }
            if (! $section->hasChildren()) {
                $this->content[] = '</li>';
            }
        }
        return implode("\n", $this->content);
    }
}
