<?php
/* Icinga Web 2 | (c) 2019 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Monitoring\ProvidedHook\X509;

use Icinga\Data\Filter\Filter;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use Icinga\Module\X509\Hook\SniHook;

class Sni extends SniHook
{
    public function getHosts(Filter $filter = null)
    {
        MonitoringBackend::clearInstances();

        $hosts = MonitoringBackend::instance()
            ->select()
            ->from('hoststatus', [
                'host_name',
                'host_address',
                'host_address6'
            ]);
        if ($filter !== null) {
            $hosts->applyFilter($filter);
        }

        foreach ($hosts as $host) {
            if (! empty($host->host_address)) {
                yield $host->host_address => $host->host_name;
            }

            if (! empty($host->host_address6)) {
                yield $host->host_address6 => $host->host_name;
            }
        }
    }
}
