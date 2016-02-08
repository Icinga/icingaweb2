<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

use ArrayIterator;
use Exception;
use RecursiveIterator;
use Icinga\Application\Icinga;
use Icinga\Exception\IcingaException;
use Icinga\Web\Navigation\Navigation;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\View;

/**
 * Renderer for single level navigation
 */
class NavigationRenderer implements RecursiveIterator, NavigationRendererInterface
{
    /**
     * The tag used for the outer element
     *
     * @var string
     */
    protected $elementTag;

    /**
     * The CSS class used for the outer element
     *
     * @var string
     */
    protected $cssClass;

    /**
     * The navigation's heading text
     *
     * @var string
     */
    protected $heading;

    /**
     * The content rendered so far
     *
     * @var array
     */
    protected $content;

    /**
     * Whether to skip rendering the outer element
     *
     * @var bool
     */
    protected $skipOuterElement;

    /**
     * The navigation's iterator
     *
     * @var ArrayIterator
     */
    protected $iterator;

    /**
     * The navigation
     *
     * @var Navigation
     */
    protected $navigation;

    /**
     * View
     *
     * @var View
     */
    protected $view;

    /**
     * Create a new NavigationRenderer
     *
     * @param   Navigation  $navigation
     * @param   bool        $skipOuterElement
     */
    public function __construct(Navigation $navigation, $skipOuterElement = false)
    {
        $this->skipOuterElement = $skipOuterElement;
        $this->iterator = $navigation->getIterator();
        $this->navigation = $navigation;
        $this->content = array();
    }

    /**
     * {@inheritdoc}
     */
    public function setElementTag($tag)
    {
        $this->elementTag = $tag;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getElementTag()
    {
        return $this->elementTag ?: static::OUTER_ELEMENT_TAG;
    }

    /**
     * {@inheritdoc}
     */
    public function setCssClass($class)
    {
        $this->cssClass = $class;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getCssClass()
    {
        return $this->cssClass;
    }

    /**
     * {@inheritdoc}
     */
    public function setHeading($heading)
    {
        $this->heading = $heading;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getHeading()
    {
        return $this->heading;
    }

    /**
     * Return the view
     *
     * @return View
     */
    public function view()
    {
        if ($this->view === null) {
            $this->setView(Icinga::app()->getViewRenderer()->view);
        }

        return $this->view;
    }

    /**
     * Set the view
     *
     * @param   View    $view
     *
     * @return  $this
     */
    public function setView(View $view)
    {
        $this->view = $view;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getChildren()
    {
        return new static($this->current()->getChildren(), $this->skipOuterElement);
    }

    /**
     * {@inheritdoc}
     */
    public function hasChildren()
    {
        return $this->current()->hasChildren();
    }

    /**
     * {@inheritdoc}
     *
     * @return  NavigationItem
     */
    public function current()
    {
        return $this->iterator->current();
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->iterator->key();
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->iterator->next();
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->iterator->rewind();
        if (! $this->skipOuterElement) {
            $this->content[] = $this->beginMarkup();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        $valid = $this->iterator->valid();
        if (! $this->skipOuterElement && !$valid) {
            $this->content[] = $this->endMarkup();
        }

        return $valid;
    }

    /**
     * Return the opening markup for the navigation
     *
     * @return  string
     */
    public function beginMarkup()
    {
        $content = array();
        $content[] = sprintf(
            '<%s%s role="navigation">',
            $this->getElementTag(),
            $this->getCssClass() !== null ? ' class="' . $this->getCssClass() . '"' : ''
        );
        if (($heading = $this->getHeading()) !== null) {
            $content[] = sprintf(
                '<h%1$d id="navigation" class="sr-only" tabindex="-1">%2$s</h%1$d>',
                static::HEADING_RANK,
                $this->view()->escape($heading)
            );
        }
        $content[] = $this->beginChildrenMarkup();
        return join("\n", $content);
    }

    /**
     * Return the closing markup for the navigation
     *
     * @return  string
     */
    public function endMarkup()
    {
        $content = array();
        $content[] = $this->endChildrenMarkup();
        $content[] = '</' . $this->getElementTag() . '>';
        return join("\n", $content);
    }

    /**
     * Return the opening markup for multiple navigation items
     *
     * @param   int $level
     *
     * @return  string
     */
    public function beginChildrenMarkup($level = 1)
    {
        $cssClass = array(static::CSS_CLASS_NAV);
        if ($this->navigation->getLayout() === Navigation::LAYOUT_TABS) {
            $cssClass[] = static::CSS_CLASS_NAV_TABS;
        } elseif ($this->navigation->getLayout() === Navigation::LAYOUT_DROPDOWN) {
            $cssClass[] = static::CSS_CLASS_NAV_DROPDOWN;
        }

        $cssClass[] = 'nav-level-' . $level;

        return '<ul class="' . join(' ', $cssClass) . '">';
    }

    /**
     * Return the closing markup for multiple navigation items
     *
     * @return  string
     */
    public function endChildrenMarkup()
    {
        return '</ul>';
    }

    /**
     * Return the opening markup for the given navigation item
     *
     * @param   NavigationItem  $item
     *
     * @return  string
     */
    public function beginItemMarkup(NavigationItem $item)
    {
        $cssClasses = array(static::CSS_CLASS_ITEM);

        if ($item->hasChildren() && $item->getChildren()->getLayout() === Navigation::LAYOUT_DROPDOWN) {
            $cssClasses[] = static::CSS_CLASS_DROPDOWN;
            $item
                ->setAttribute('class', static::CSS_CLASS_DROPDOWN_TOGGLE)
                ->setIcon(static::DROPDOWN_TOGGLE_ICON)
                ->setUrl('#');
        }

        if ($item->getActive()) {
            $cssClasses[] = static::CSS_CLASS_ACTIVE;
        }

        if ($item->getIcon() === null) {
            // @TODO(el): Add constant
            $cssClasses[] = 'no-icon';
        }

        if ($cssClass = $item->getCssClass()) {
            $cssClasses[] = $cssClass;
        }

        $content = sprintf(
            '<li class="%s">',
            join(' ', $cssClasses)
        );
        return $content;
    }

    /**
     * Return the closing markup for a navigation item
     *
     * @return  string
     */
    public function endItemMarkup()
    {
        return '</li>';
    }

    /**
     * {@inheritdoc}
     */
    public function render()
    {
        foreach ($this as $item) {
            /** @var NavigationItem $item */
            if ($item->shouldRender()) {
                $content = $item->render();
                $this->content[] = $this->beginItemMarkup($item);
                $this->content[] = $content;
                $this->content[] = $this->endItemMarkup();
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
