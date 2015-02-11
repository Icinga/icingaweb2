<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Search;

use RecursiveIteratorIterator;
use Icinga\Module\Doc\DocRenderer;

/**
 * Renderer for doc searches
 *
 * @method DocSearchIterator getInnerIterator() {
 *     @{inheritdoc}
 * }
 */
class DocSearchRenderer extends DocRenderer
{
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
        $this->content[] = '<nav role="navigation"><ul class="toc">';
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
            $this->content[] = '<ul class="toc">';
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
                    $title = $match->highlight();
                } else {
                    $contentMatches[] = sprintf(
                        '<p>%s</p>',
                        $match->highlight()
                    );
                }
            }
            $path = $this->getView()->getHelper('Url')->url(
                array_merge(
                    $this->getUrlParams(),
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
                array('highlight-search' => $this->getInnerIterator()->getSearch()->getInput())
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
