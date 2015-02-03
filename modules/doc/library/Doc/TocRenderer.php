<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | http://www.gnu.org/licenses/gpl-2.0.txt */

namespace Icinga\Module\Doc;

use RecursiveIteratorIterator;
use Zend_View_Helper_Url;
use Icinga\Web\View;

/**
 * TOC renderer
 */
class TocRenderer extends Renderer
{
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
     * Content
     *
     * @var array
     */
    protected $content = array();

    /**
     * Create a new toc renderer
     *
     * @param DocTree   $docTree    The documentation tree
     * @param string    $url        The URL to replace links with
     * @param array     $urlParams  Additional URL parameters
     */
    public function __construct(DocTree $docTree, $url, array $urlParams)
    {
        parent::__construct($docTree, RecursiveIteratorIterator::SELF_FIRST);
        $this->url = $url;
        $this->urlParams = array_map(array($this, 'encodeUrlParam'), $urlParams);
    }

    public function beginIteration()
    {
        $this->content[] = '<nav><ul>';
    }

    public function endIteration()
    {
        $this->content[] = '</ul></nav>';
    }

    public function beginChildren()
    {
        $this->content[] = '<ul>';
    }

    public function endChildren()
    {
        $this->content[] = '</ul></li>';
    }

    /**
     * Render the toc
     *
     * @param   View                    $view
     * @param   Zend_View_Helper_Url    $zendUrlHelper
     *
     * @return  string
     */
    public function render(View $view, Zend_View_Helper_Url $zendUrlHelper)
    {
        foreach ($this as $node) {
            $section = $node->getValue();
            /* @var $section \Icinga\Module\Doc\Section */
            $path = $zendUrlHelper->url(
                array_merge(
                    $this->urlParams,
                    array(
                        'chapterId' => $this->encodeUrlParam($section->getChapterId())
                    )
                ),
                $this->url,
                false,
                false
            );
            $url = $view->url($path);
            $url->setAnchor($this->encodeAnchor($section->getId()));
            $this->content[] = sprintf(
                '<li><a %shref="%s">%s</a>',
                $section->isNoFollow() ? 'rel="nofollow" ' : '',
                $url->getAbsoluteUrl(),
                $view->escape($section->getTitle())
            );
            if (! $this->getInnerIterator()->current()->hasChildren()) {
                $this->content[] = '</li>';
            }
        }
        return implode("\n", $this->content);
    }
}
