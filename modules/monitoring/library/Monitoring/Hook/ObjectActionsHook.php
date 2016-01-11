<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

use Icinga\Web\Navigation\Navigation;
use Icinga\Module\Monitoring\Object\MonitoredObject;

/**
 * Base class for object action hooks
 */
abstract class ObjectActionsHook
{
    /**
     * Return the action navigation for the given object
     *
     * @return  Navigation
     */
    public function getNavigation(MonitoredObject $object)
    {
        $urls = $this->getActionsForHost($object);
        if (is_array($urls)) {
            $navigation = new Navigation();
            foreach ($urls as $label => $url) {
                $navigation->addItem($label, array('url' => $url));
            }
        } else {
            $navigation = $urls;
        }

        return $navigation;
    }

    /**
     * Create and return a new Navigation object
     *
     * @param   array   $actions    Optional array of actions to add to the returned object
     *
     * @return  Navigation
     */
    protected function createNavigation(array $actions = null)
    {
        return empty($actions) ? new Navigation() : Navigation::fromArray($actions);
    }
}
