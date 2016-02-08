<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

use Icinga\Module\Monitoring\Object\Service;
use Icinga\Module\Monitoring\Object\MonitoredObject;

/**
 * Base class for host action hooks
 */
abstract class ServiceActionsHook extends ObjectActionsHook
{
    /**
     * Implementors of this method should return an array containing
     * additional action links for a specific host. You get a full Service
     * object, which allows you to return specific links only for nodes
     * with specific properties.
     *
     * The result array should be in the form title => url, where title will
     * be used as link caption. Url should be an Icinga\Web\Url object when
     * the link should point to an Icinga Web url - otherwise a string would
     * be fine.
     *
     * Mixed example:
     * <code>
     * return array(
     *     'Wiki' => 'http://my.wiki/host=' . rawurlencode($service->service_name),
     *     'Logstash' => Url::fromPath(
     *         'logstash/search/syslog',
     *         array('service' => $service->host_name)
     *     )
     * );
     * </code>
     *
     * One might also provide ssh:// or rdp:// urls if equipped with fitting
     * (safe) URL handlers for his browser(s).
     *
     * TODO: I'd love to see some kind of a Link/LinkSet object implemented
     *       for this and similar hooks.
     *
     * @param   Service $service Monitoring service object
     *
     * @return  array   An array containing a list of service action links
     */
    abstract public function getActionsForService(Service $service);

    public function getActionsForObject(MonitoredObject $object)
    {
        return $this->getActionsForService($object);
    }
}
