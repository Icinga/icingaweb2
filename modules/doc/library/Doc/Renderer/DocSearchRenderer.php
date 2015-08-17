<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Renderer;

use RecursiveIteratorIterator;
use Icinga\Module\Doc\Search\DocSearchIterator;
use Icinga\Module\Doc\Search\DocSearchMatch;

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
     * @var array
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
            /** @var \Icinga\Web\Url $url */
            $url->setAnchor($this->encodeAnchor($section->getId()));
            $urlAttributes = array(
                'data-base-target'  => '_next',
                'title'             => sprintf(
                    $this->getView()->translate(
                        'Show all matches of "%s" in %sthe chapter "%s"',
                        'search.render.section.link'
                    ),
                    $this->getInnerIterator()->getSearch()->getInput(),
                    $section->getId() !== $section->getChapter()->getId() ? sprintf(
                        $this->getView()->translate('the section "%s" of ', 'search.render.section.link'),
                        $section->getTitle()
                    ) : '',
                    $section->getChapter()->getTitle()
                )
            );
            if ($section->getNoFollow()) {
                $urlAttributes['rel'] = 'nofollow';
            }
            $this->content[] = '<li>' . $this->getView()->qlink(
                $title,
                $url->getAbsoluteUrl(),
                null,
                $urlAttributes,
                false
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
