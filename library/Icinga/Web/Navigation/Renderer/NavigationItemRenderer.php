<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

use Icinga\Application\Icinga;
use Icinga\Exception\ProgrammingError;
use Icinga\Util\StringHelper;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Web\Url;
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
     * Internal link targets provided by Icinga Web 2
     *
     * @var array
     */
    protected $internalLinkTargets;

    /**
     * Whether to escape the label
     *
     * @var bool
     */
    protected $escapeLabel;

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

        $this->internalLinkTargets = array('_main', '_self', '_next');
        $this->init();
    }

    /**
     * Initialize this renderer
     */
    public function init()
    {
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
            $setter = 'set' . StringHelper::cname($name);
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
     * Set whether to escape the label
     *
     * @param   bool    $state
     *
     * @return  $this
     */
    public function setEscapeLabel($state = true)
    {
        $this->escapeLabel = (bool) $state;
        return $this;
    }

    /**
     * Return whether to escape the label
     *
     * @return  bool
     */
    public function getEscapeLabel()
    {
        return $this->escapeLabel !== null ? $this->escapeLabel : true;
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

        $label = $this->getEscapeLabel()
            ? $this->view()->escape($item->getLabel())
            : $item->getLabel();
        if (($icon = $item->getIcon()) !== null) {
            $label = $this->view()->icon($icon) . $label;
        }

        if (($url = $item->getUrl()) !== null) {
            $url->overwriteParams($item->getUrlParameters());

            $target = $item->getTarget();
            if ($url->isExternal() && (!$target || in_array($target, $this->internalLinkTargets, true))) {
                $url = Url::fromPath('iframe', array('url' => $url));
            }

            $content = sprintf(
                '<a%s href="%s"%s>%s</a>',
                $this->view()->propertiesToString($item->getAttributes()),
                $this->view()->escape($url->getAbsoluteUrl('&')),
                $this->renderTargetAttribute(),
                $label
            );
        } elseif ($label) {
            $content = sprintf(
                '<%1$s%2$s>%3$s</%1$s>',
                $item::LINK_ALTERNATIVE,
                $this->view()->propertiesToString($item->getAttributes()),
                $label
            );
        } else {
            $content = '';
        }

        return $content;
    }

    /**
     * Render and return the attribute to provide a non-default target for the url
     *
     * @return  string
     */
    protected function renderTargetAttribute()
    {
        $target = $this->getItem()->getTarget();
        if ($target === null || $this->getItem()->getUrl()->getAbsoluteUrl() == '#') {
            return '';
        }

        if (! in_array($target, $this->internalLinkTargets, true)) {
            return ' target="' . $this->view()->escape($target) . '"';
        }

        return ' data-base-target="' . $target . '"';
    }
}
