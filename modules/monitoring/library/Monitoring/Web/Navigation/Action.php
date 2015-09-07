<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Web\Navigation;

use Icinga\Web\Navigation\NavigationItem;
use Icinga\Module\Monitoring\Object\Macro;
use Icinga\Module\Monitoring\Object\MonitoredObject;

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
     * {@inheritdoc}
     */
    public function getUrl()
    {
        $url = parent::getUrl();
        if (! $this->resolved && $url !== null) {
            $this->setUrl(Macro::resolveMacros($url->getAbsoluteUrl(), $this->getObject()));
            $this->resolved = true;
        }

        return $url;
    }
}
