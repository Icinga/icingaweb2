<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;
use Icinga\Util\String;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\View;

/**
 * NavigationItemRenderer
 */
class NavigationItemRenderer
{
    /**
     * View
     *
     * @var View
     */
    protected $view;

    /**
     * The item being rendered
     *
     * @var NavigationItem
     */
    protected $item;

    /**
     * The link target
     *
     * @var string
     */
    protected $target;

    /**
     * Create a new NavigationItemRenderer
     *
     * @param   array   $options
     */
    public function __construct(array $options = null)
    {
        if (! empty($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Set the given options
     *
     * @param   array   $options
     *
     * @return  $this
     */
    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $setter = 'set' . String::cname($name);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }
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
     * Return the view
     *
     * @return  View
     */
    public function view()
    {
        if ($this->view === null) {
            $this->setView(Icinga::app()->getViewRenderer()->view);
        }

        return $this->view;
    }

    /**
     * Set the navigation item to render
     *
     * @param   NavigationItem  $item
     *
     * @return  $this
     */
    public function setItem(NavigationItem $item)
    {
        $this->item = $item;
        return $this;
    }

    /**
     * Return the navigation item being rendered
     *
     * @return  NavigationItem
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * Set the link target
     *
     * @param   string  $target
     *
     * @return  $this
     */
    public function setTarget($target)
    {
        $this->target = $target;
        return $this;
    }

    /**
     * Return the link target
     *
     * @return  string
     */
    public function getTarget()
    {
        return $this->target;
    }

    /**
     * Render the given navigation item as HTML anchor
     *
     * @param   NavigationItem  $item
     *
     * @return  string
     */
    public function render(NavigationItem $item = null)
    {
        if ($item !== null) {
            $this->setItem($item);
        } elseif (($item = $this->getItem()) === null) {
            throw new ProgrammingError(
                'Cannot render nothing. Pass the item to render as part'
                . ' of the call to render() or set it with setItem()'
            );
        }

        $label = $this->view()->escape($item->getLabel());
        if (($icon = $item->getIcon()) !== null) {
            $label = $this->view()->icon($icon) . $label;
        }

        if (($url = $item->getUrl()) !== null) {
            $content = sprintf(
                '<a%s href="%s"%s>%s</a>',
                $this->view()->propertiesToString($item->getAttributes()),
                $this->view()->url($url, $item->getUrlParameters()),
                $this->target ? ' target="' . $this->view()->escape($this->target) . '"' : '',
                $label
            );
        } else {
            $content = sprintf(
                '<%1$s%2$s>%3$s</%1$s>',
                $item::LINK_ALTERNATIVE,
                $this->view()->propertiesToString($item->getAttributes()),
                $label
            );
        }

        return $content;
    }
}
