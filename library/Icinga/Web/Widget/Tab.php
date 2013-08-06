<?php

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
 */
class Tab implements Widget
{
    /**
     * Whether this tab is currently active
     *
     * @var bool
     */
    private $active = false;

    /**
     * Default values for widget properties
     *
     * @var array
     */
    private $name = null;

    private $title = '';
    private $url = null;
    private $urlParams = array();
    private $icon = null;


    /**
     * @param mixed $icon
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
    }

    /**
     * @return mixed
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param mixed $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    public function __construct(array $properties = array())
    {
        foreach ($properties as $name=>$value) {
            $setter = 'set'.ucfirst($name);
            if (method_exists($this, $setter)) {
                $this->$setter($value);
            }
        }

    }

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
            throw new ProgrammingError('Cannot create a nameless tab');
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
    public function render(\Zend_View_Abstract $view)
    {
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
