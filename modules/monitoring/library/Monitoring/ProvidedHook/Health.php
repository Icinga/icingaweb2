<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Monitoring\ProvidedHook;

use Icinga\Application\Hook\HealthHook;
use Icinga\Date\DateFormatter;
use Icinga\Module\Monitoring\Backend\MonitoringBackend;
use ipl\Web\Url;

class Health extends HealthHook
{
    /** @var object */
    protected $programStatus;

    public function getName()
    {
        return 'Icinga';
    }

    public function getUrl()
    {
        return Url::fromPath('monitoring/health/info');
    }

    public function checkHealth()
    {
        $backendName = MonitoringBackend::instance()->getName();
        $programStatus = $this->getProgramStatus();
        if ($programStatus === false) {
            $this->setState(self::STATE_UNKNOWN);
            $this->setMessage(sprintf(t('%s is currently not up and running'), $backendName));
            return;
        }

        if ($programStatus->is_currently_running) {
            $this->setState(self::STATE_OK);
            $this->setMessage(sprintf(
                t(
                    '%1$s has been up and running with PID %2$d %3$s',
                    'Last format parameter represents the time running'
                ),
                $backendName,
                $programStatus->process_id,
                DateFormatter::timeSince($programStatus->program_start_time)
            ));
        } else {
            $this->setState(self::STATE_CRITICAL);
            $this->setMessage(sprintf(t('Backend %s is not running'), $backendName));
        }

        $this->setMetrics((array) $programStatus);
    }

    protected function getProgramStatus()
    {
        if ($this->programStatus === null) {
            $this->programStatus = MonitoringBackend::instance()->select()
                ->from('programstatus', [
                    'program_version',
                    'status_update_time',
                    'program_start_time',
                    'program_end_time',
                    'endpoint_name',
                    'is_currently_running',
                    'process_id',
                    'last_command_check',
                    'last_log_rotation',
                    'notifications_enabled',
                    'active_service_checks_enabled',
                    'active_host_checks_enabled',
                    'event_handlers_enabled',
                    'flap_detection_enabled',
                    'process_performance_data'
                ])
                ->fetchRow();
        }

        return $this->programStatus;
    }
}
