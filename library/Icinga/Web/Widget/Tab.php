<?php

/**
 * Single tab
 */
namespace Icinga\Web\Widget;

use Icinga\Exception\ProgrammingError;

/**
 * A single tab, usually used through the tabs widget
 *
 * Will generate an &lt;li&gt; list item, with an optional link and icon
 *
 * @property string $name      Tab identifier
 * @property string $title     Tab title
 * @property string $icon      Icon URL, preferrably relative to the Icinga
 *                             base URL
 * @property string $url       Action URL, preferrably relative to the Icinga
 *                             base URL
 * @property string $urlParams Action URL Parameters
 *
 * @copyright  Copyright (c) 2013 Icinga-Web Team <info@icinga.org>
 * @author     Icinga-Web Team <info@icinga.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @deprecated Because of HTML creation of PHP<
 */
class Tab extends AbstractWidget
{
    /**
     * Whether this tab is currently active
     *
     * @var bool
     */
    protected $active = false;

    /**
     * Default values for widget properties
     *
     * @var array
     */
    protected $properties = array(
        'name'      => null,
        'title'     => '',
        'url'       => null,
        'urlParams' => array(),
        'icon'      => null,
    );

    /**
     * Health check at initialization time
     *
     * @throws Icinga\Exception\ProgrammingError if tab name is missing
     *
     * @return void
     */
    protected function init()
    {
        if ($this->name === null) {
            throw new ProgrammingError(
                'Cannot create a nameless tab'
            );
        }
    }

    /**
     * Set this tab active (default) or inactive
     *
     * This is usually done through the tabs container widget, therefore it
     * is not a good idea to directly call this function
     *
     * @param  bool $active Whether the tab should be active
     *
     * @return self
     */
    public function setActive($active = true)
    {
        $this->active = (bool) $active;
        return $this;
    }

    /**
     * Whether this tab is currently active
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * This is where the list item HTML is created
     *
     * @return string
     */
    public function renderAsHtml()
    {
        $view = $this->view();
        $class = $this->isActive() ? ' class="active"' : '';
        $caption = $this->title;
        if ($this->icon !== null) {
            $caption = $view->img($this->icon, array(
                'width'  => 16,
                'height' => 16
            )) . ' ' . $caption;
        }
        if ($this->url !== null) {
            $tab = $view->qlink(
                $caption,
                $this->url,
                $this->urlParams,
                array('quote' => false)
            );
        } else {
            $tab = $caption;
        }
        return "<li $class>$tab</li>\n";
    }
}
