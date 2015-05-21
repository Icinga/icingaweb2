<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

/**
 * Helper for generating frequently used jump links
 *
 * Most of the monitoring overviews link to detail information, e.g. the full information of the involved monitored
 * object. Instead of reintroducing link generation and translation in those views, this helper contains most
 * frequently used jump links.
 */
class Zend_View_Helper_Link extends Zend_View_Helper_Abstract
{
    /**
     * Helper entry point
     *
     * @return $this
     */
    public function link()
    {
        return $this;
    }

    /**
     * Create a host link
     *
     * @param   string  $host       Hostname
     * @param   string  $linkText   Link text, e.g. the host's display name
     *
     * @return  string
     */
    public function host($host, $linkText)
    {
        return $this->view->qlink(
            $linkText,
            'monitoring/host/show',
            array('host' => $host),
            array('title' => sprintf($this->view->translate('Show detailed information for host %s'), $host))
        );
    }

    /**
     * Create a service link
     *
     * @param   string  $service            Service name
     * @param   string  $serviceLinkText    Text for the service link, e.g. the service's display name
     * @param   string  $host               Hostname
     * @param   string  $hostLinkText       Text for the host link, e.g. the host's display name
     *
     * @return  string
     */
    public function service($service, $serviceLinkText, $host, $hostLinkText)
    {
        return sprintf(
            $this->view->translate('%s on %s', 'Service running on host'),
            $this->view->qlink(
                $serviceLinkText,
                'monitoring/service/show',
                array('host' => $host, 'service' => $service),
                array('title' => sprintf(
                    $this->view->translate('Show detailed information for service %s on host %s'),
                    $service,
                    $host
                ))
            ),
            $this->host($host, $hostLinkText)
        );
    }
}
