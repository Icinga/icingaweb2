<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Doc;

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
class TocRenderer extends Renderer
{
    /**
     * Content to render
     *
     * @type array
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
        $this->content[] = '<ul class="toc">';
    }

    /**
     * {@inheritdoc}
     */
    public function endChildren()
    {
        $this->content[] = '</ul>';
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
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
            /** @type \Icinga\Web\Url $url */
            $url->setAnchor($this->encodeAnchor($section->getId()));
            $this->content[] = sprintf(
                '<li><a data-base-target="_next" %shref="%s">%s</a>',
                $section->getNoFollow() ? 'rel="nofollow" ' : '',
                $url->getAbsoluteUrl(),
                $view->escape($section->getTitle())
            );
            if (! $section->hasChildren()) {
                $this->content[] = '</li>';
            }
        }
        return implode("\n", $this->content);
    }
}
