<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

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
            /** @var \Icinga\Web\Url $url */
            $url->setAnchor($this->encodeAnchor($section->getId()));
            $urlAttributes = array(
                'data-base-target'  => '_next',
                'title'             => sprintf(
                    $this->getView()->translate('Show the %schapter "%s"', 'toc.render.section.link'),
                    $section->getId() !== $section->getChapter()->getId() ? sprintf(
                        $this->getView()->translate('section "%s" of the ', 'toc.render.section.link'),
                        $section->getTitle()
                    ) : '',
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
