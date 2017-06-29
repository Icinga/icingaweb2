<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc\Renderer;

use Icinga\Web\View;
use Icinga\Data\Tree\TreeNodeIterator;
use RecursiveIteratorIterator;

/**
 * TOC renderer
 *
 * @method TreeNodeIterator getInnerIterator() {
 *     {@inheritdoc}
 * }
 */
class DocTocRenderer extends DocRenderer
{
    /**
     * CSS class for the HTML list element
     *
     * @var string
     */
    const CSS_CLASS = 'toc';

    /**
     * Tag for the HTML list element
     *
     * @var string
     */
    const HTML_LIST_TAG = 'ol';

    /**
     * Content to render
     *
     * @var array
     */
    protected $content = array();

    /**
     * Create a new toc renderer
     *
     * @param TreeNodeIterator $iterator
     */
    public function __construct(TreeNodeIterator $iterator)
    {
        parent::__construct($iterator, RecursiveIteratorIterator::SELF_FIRST);
    }

    /**
     * {@inheritdoc}
     */
    public function beginIteration()
    {
        $this->content[] = sprintf('<nav role="navigation"><%s class="%s">', static::HTML_LIST_TAG, static::CSS_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function endIteration()
    {
        $this->content[] = sprintf('</%s></nav>', static::HTML_LIST_TAG);
    }

    /**
     * {@inheritdoc}
     */
    public function beginChildren()
    {
        $this->content[] = sprintf('<%s class="%s">', static::HTML_LIST_TAG, static::CSS_CLASS);
    }

    /**
     * {@inheritdoc}
     */
    public function endChildren()
    {
        $this->content[] = sprintf('</%s>', static::HTML_LIST_TAG);
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        if ($this->getInnerIterator()->isEmpty()) {
            return '<p>' . mt('doc', 'Documentation is empty.') . '</p>';
        }
        $view = $this->getView();
        $zendUrlHelper = $view->getHelper('Url');
        foreach ($this as $section) {
            $path = $zendUrlHelper->url(
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
            $url = $view->url($path);
            /** @var \Icinga\Web\Url $url */
            if ($this->getDepth() > 0) {
                $url->setAnchor($this->encodeAnchor($section->getId()));
            }
            $urlAttributes = array(
                'data-base-target'  => '_next',
                'title'             => $section->getId() === $section->getChapter()->getId()
                    ? sprintf(
                        $view->translate('Show the chapter "%s"', 'toc.render.section.link'),
                        $section->getChapter()->getTitle()
                    )
                    : sprintf(
                        $view->translate('Show the section "%s" of the chapter "%s"', 'toc.render.section.link'),
                        $section->getTitle(),
                        $section->getChapter()->getTitle()
                    )
            );
            if ($section->getNoFollow()) {
                $urlAttributes['rel'] = 'nofollow';
            }
            $this->content[] = '<li>' . $this->getView()->qlink(
                $section->getTitle(),
                $url->getAbsoluteUrl(),
                null,
                $urlAttributes
            );
            if (! $section->hasChildren()) {
                $this->content[] = '</li>';
            }
        }
        return implode("\n", $this->content);
    }
}
