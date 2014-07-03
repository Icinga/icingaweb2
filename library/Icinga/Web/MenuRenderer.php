<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Web;

use RecursiveIteratorIterator;

/**
 * A renderer to draw a menu with its sub-menus using an unordered html list
 */
class MenuRenderer extends RecursiveIteratorIterator
{
    /**
     * The relative url of the current request
     *
     * @var string
     */
    protected $url;

    /**
     * The html tags to assemble the menu with
     *
     * @var array
     */
    protected $tags = array();

    /**
     * Create a new MenuRenderer
     *
     * @param   Menu    $menu   The menu to render
     * @param   string  $url    A relative url to identify "active" children with
     */
    public function __construct(Menu $menu, $url = null)
    {
        $this->url = $url;
        parent::__construct($menu, RecursiveIteratorIterator::CHILD_FIRST);
    }

    /**
     * Register the outer ul opening html-tag
     */
    public function beginIteration()
    {
        $this->tags[] = '<ul role="navigation">';
    }

    /**
     * Register the outer ul closing html-tag
     */
    public function endIteration()
    {
        $this->tags[] = '</ul>';
    }

    /**
     * Register a inner ul opening html-tag
     */
    public function beginChildren()
    {
        // The iterator runs in mode CHILD_FIRST so we need to remember
        // where to insert the parent's opening html tag once its rendered
        $parent = $this->getSubIterator(0)->current();
        $this->tags[$parent->getId() . '_begin'] = null;

        $this->tags[] = '<ul>';
    }

    /**
     * Register a inner ul closing html-tag
     */
    public function endChildren()
    {
        $this->tags[] = '</ul>';

        // Remember the position of the parent's closing html-tag
        $parent = $this->getSubIterator(0)->current();
        $this->tags[$parent->getId() . '_end'] = null;
    }

    /**
     * Render the given child
     *
     * @param   Menu    $child      The menu's child to render
     *
     * @return  string              The child rendered as html
     */
    public function renderChild(Menu $child)
    {
        return sprintf(
            '<a href="%s">%s%s</a>',
            $child->getUrl() ? Url::fromPath($child->getUrl()) : '#',
            $child->getIcon() ? '<img src="' . Url::fromPath($child->getIcon()) . '" class="icon" /> ' : '',
            htmlspecialchars($child->getTitle())
        );
    }

    /**
     * Return the menu rendered as html
     *
     * @return  string
     */
    public function render()
    {
        $passedActiveChild = false;
        foreach ($this as $child) {
            $childIsActive = $this->isActive($child);
            if ($childIsActive && $this->getDepth() > 0) {
                $passedActiveChild = true;
            }

            if ($childIsActive || ($passedActiveChild && $this->getDepth() === 0)) {
                $passedActiveChild &= $this->getDepth() !== 0;
                $openTag = '<li class="active">';
            } else {
                $openTag = '<li>';
            }
            $content = $this->renderChild($child);
            $closingTag = '</li>';

            if (array_key_exists($child->getId() . '_begin', $this->tags)) {
                $this->tags[$child->getId() . '_begin'] = $openTag . $content;
                $this->tags[$child->getId() . '_end'] = $closingTag;
            } else {
                $this->tags[] = $openTag . $content . $closingTag;
            }
        }

        return implode("\n", $this->tags);
    }

    /**
     * @see MenuRenderer::render()
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Return whether the current request url references the child's url
     *
     * @param   Menu    $child      The menu's child to check
     *
     * @return  bool
     */
    protected function isActive(Menu $child)
    {
        return html_entity_decode(rawurldecode($this->url)) === html_entity_decode(rawurldecode($child->getUrl()));
    }
}
