<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Hook;

use Icinga\Module\Monitoring\Object\Host;
use Icinga\Module\Monitoring\Object\MonitoredObject;

/**
 * Base class for host action hooks
 */
abstract class HostActionsHook extends ObjectActionsHook
{
    /**
     * Implementors of this method should return an array containing
     * additional action links for a specific host. You get a full Host
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
     *     'Wiki' => 'http://my.wiki/host=' . rawurlencode($host->host_name),
     *     'Logstash' => Url::fromPath(
     *         'logstash/search/syslog',
     *         array('host' => $host->host_name)
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
     * @param   Host   $host Monitoring host object
     *
     * @return  array  An array containing a list of host action links
     */
    abstract public function getActionsForHost(Host $host);

    public function getActionsForObject(MonitoredObject $object)
    {
        return $this->getActionsForHost($object);
    }
}
