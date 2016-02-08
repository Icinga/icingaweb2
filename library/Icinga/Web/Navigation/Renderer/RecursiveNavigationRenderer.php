<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

use Exception;
use RecursiveIteratorIterator;
use Icinga\Exception\IcingaException;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\Navigation\Renderer\NavigationItemRenderer;

/**
 * Renderer for multi level navigation
 *
 * @method NavigationRenderer getInnerIterator() {
 *     {@inheritdoc}
 * }
 */
class RecursiveNavigationRenderer extends RecursiveIteratorIterator implements NavigationRendererInterface
{
    /**
     * The content rendered so far
     *
     * @var array
     */
    protected $content;

    /**
     * Whether to use the standard item renderer
     *
     * @var bool
     */
    protected $useStandardRenderer;

    /**
     * Create a new RecursiveNavigationRenderer
     *
     * @param   Navigation  $navigation
     */
    public function __construct(Navigation $navigation)
    {
        $this->content = array();
        parent::__construct(
            new NavigationRenderer($navigation, true),
            RecursiveIteratorIterator::SELF_FIRST
        );
    }

    /**
     * Set whether to use the standard navigation item renderer
     *
     * @param   bool    $state
     *
     * @return  $this
     */
    public function setUseStandardItemRenderer($state = true)
    {
        $this->useStandardRenderer = (bool) $state;
        return $this;
    }

    /**
     * Return whether to use the standard navigation item renderer
     *
     * @return  bool
     */
    public function getUseStandardItemRenderer()
    {
        return $this->useStandardRenderer;
    }

    /**
     * {@inheritdoc}
     */
    public function setElementTag($tag)
    {
        $this->getInnerIterator()->setElementTag($tag);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getElementTag()
    {
        return $this->getInnerIterator()->getElementTag();
    }

    /**
     * {@inheritdoc}
     */
    public function setCssClass($class)
    {
        $this->getInnerIterator()->setCssClass($class);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCssClass()
    {
        return $this->getInnerIterator()->getCssClass();
    }

    /**
     * {@inheritdoc}
     */
    public function setHeading($heading)
    {
        $this->getInnerIterator()->setHeading($heading);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeading()
    {
        return $this->getInnerIterator()->getHeading();
    }

    /**
     * {@inheritdoc}
     */
    public function beginIteration()
    {
        $this->content[] = $this->getInnerIterator()->beginMarkup();
    }

    /**
     * {@inheritdoc}
     */
    public function endIteration()
    {
        $this->content[] = $this->getInnerIterator()->endMarkup();
    }

    /**
     * {@inheritdoc}
     */
    public function beginChildren()
    {
        $this->content[] = $this->getInnerIterator()->beginChildrenMarkup($this->getDepth() + 1);
    }

    /**
     * {@inheritdoc}
     */
    public function endChildren()
    {
        $this->content[] = $this->getInnerIterator()->endChildrenMarkup();
        $this->content[] = $this->getInnerIterator()->endItemMarkup();
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        foreach ($this as $item) {
            /** @var NavigationItem $item */
            if ($item->shouldRender()) {
                if ($this->getUseStandardItemRenderer()) {
                    $renderer = new NavigationItemRenderer();
                    $content = $renderer->render($item);
                } else {
                    $content = $item->render();
                }
                $this->content[] = $this->getInnerIterator()->beginItemMarkup($item);

                $this->content[] = $content;

                if (! $item->hasChildren()) {
                    $this->content[] = $this->getInnerIterator()->endItemMarkup();
                }
            }
        }

        return join("\n", $this->content);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (Exception $e) {
            return IcingaException::describe($e);
        }
    }
}
