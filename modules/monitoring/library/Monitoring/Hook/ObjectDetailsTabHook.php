<?php
/* Icinga Web 2 | (c) 2016 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

use Icinga\Authentication\Auth;
use Icinga\Module\Monitoring\Object\MonitoredObject;
use Icinga\Web\Request;

/**
 * Base class for object host details custom tab hooks
 */
abstract class ObjectDetailsTabHook
{
    /**
     * Return the tab name - it must be unique
     *
     * @return  string
     */
    abstract public function getName();

    /**
     * Return the tab label
     *
     * @return  string
     */
    abstract public function getLabel();

    /**
     * Return the tab header
     *
     * @param MonitoredObject $monitoredObject The monitored object related to that page
     * @param Request $request
     * @return  string/bool The HTML string that compose the tab header,
     *          bool True if the default header should be shown, False to display nothing
     */
    public function getHeader(MonitoredObject $monitoredObject, Request $request)
    {
        return true;
    }

    /**
     * Return the tab content
     *
     * @param MonitoredObject $monitoredObject The monitored object related to that page
     * @param Request $request
     * @return  string The HTML string that compose the tab content
     */
    abstract public function getContent(MonitoredObject $monitoredObject, Request $request);

    /**
     * This method returns true if the tab is visible for the logged user, otherwise false
     *
     * @return  bool True if the tab is visible for the logged user, otherwise false
     */
    public function shouldBeShown(MonitoredObject $monitoredObject, Auth $auth)
    {
        return true;
    }
}
