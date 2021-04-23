<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Navigation;

use Icinga\Data\Filter\Filter;
use Icinga\Web\Navigation\NavigationItem;
use Icinga\Module\Monitoring\Object\Macro;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Web\Url;

/**
 * Action for monitored objects
 */
class Action extends NavigationItem
{
    /**
     * Whether this action's macros were already resolved
     *
     * @var bool
     */
    protected $resolved = false;

    /**
     * This action's object
     *
     * @var MonitoredObject
     */
    protected $object;

    /**
     * The filter to use when being asked whether to render this action
     *
     * @var string
     */
    protected $filter;

    /**
     * This action's raw url attribute
     *
     * @var string
     */
    protected $rawUrl;

    /**
     * Set this action's object
     *
     * @param   MonitoredObject     $object
     *
     * @return  $this
     */
    public function setObject(MonitoredObject $object)
    {
        $this->object = $object;
        return $this;
    }

    /**
     * Return this action's object
     *
     * @return  MonitoredObject
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * Set the filter to use when being asked whether to render this action
     *
     * @param   string  $filter
     *
     * @return  $this
     */
    public function setFilter($filter)
    {
        $this->filter = $filter;
        return $this;
    }

    /**
     * Return the filter to use when being asked whether to render this action
     *
     * @return  string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    public function setUrl($url)
    {
        if (is_string($url)) {
            $this->rawUrl = $url;
        } else {
            parent::setUrl($url);
        }

        return $this;
    }

    public function getUrl()
    {
        $url = parent::getUrl();
        if (! $this->resolved && $url === null && $this->rawUrl !== null) {
            $this->setUrl(Url::fromPath(Macro::resolveMacros($this->rawUrl, $this->getObject())));
            $this->resolved = true;
            return parent::getUrl();
        } else {
            return $url;
        }
    }

    public function getRender()
    {
        if ($this->render === null) {
            $filter = $this->getFilter();
            $this->render = $filter ? Filter::fromQueryString($filter)->matches($this->getObject()) : true;
        }

        return $this->render;
    }
}
