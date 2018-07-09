<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\ProvidedHook;

use Icinga\Application\Hook\ApplicationStateHook;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;

class ApplicationState extends ApplicationStateHook
{
    public function collectMessages()
    {
        $backend = MonitoringBackend::instance();

        $programStatus = $backend
            ->select()
            ->from(
                'programstatus',
                ['is_currently_running', 'status_update_time']
            )
            ->fetchRow();

        if ($programStatus === false || ! (bool) $programStatus->is_currently_running) {
            $message = sprintf(
                mt('monitoring', "Monitoring backend '%s' is not running."),
                $backend->getName()
            );

            $this->addError('monitoring/backend-down', $programStatus->status_update_time, $message);
        }
    }
}
