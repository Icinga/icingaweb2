<?php
/* Icinga Web 2 | (c) 2022 Icinga GmbH | GPLv2+ */

namespace Icinga\Common;

use Icinga\Application\Hook\HealthHook;
use Icinga\Module\Icingadb\Model\State;
use ipl\Web\Widget\StateBadge;

trait HealthBadgeTrait
{
    private $STATE_OK = 'ok';
    private $STATE_CRITICAL = 'critical';
    private $STATE_WARNING = 'warning';
    private $STATE_PENDING = 'pending';
    private $STATE_UNKNOWN = 'unknown';

    /** @var string The state of the worst health problem item */
    protected $state;

    /** @var string The message of the worst health problem item */
    protected $title;

    /**
     * Create Health Badge
     *
     * @return ?StateBadge
     */
    protected function createHealthBadge()
    {
        $stateBadge = null;
        if ($this->getHealthCount() > 0) {
            $stateBadge = new StateBadge($this->getHealthCount(), $this->state);
            $stateBadge->addAttributes(['class' => 'disabled', 'title' => $this->title]);
        }

        return $stateBadge;
    }

    /**
     * Get the number of health problems
     *
     * @return int
     */
    protected function getHealthCount():int
    {
        $count = 0;
        $title = null;
        $worstState = null;
        foreach (HealthHook::collectHealthData()->select() as $result) {
            if ($worstState === null || $result->state > $worstState) {
                $worstState = $result->state;
                $title = $result->message;
                $count = 1;
            } elseif ($worstState === $result->state) {
                $count++;
            }
        }

        switch ($worstState) {
            case HealthHook::STATE_OK:
                $count = 0;
                break;
            case HealthHook::STATE_WARNING:
                $this->state = $this->STATE_WARNING;
                break;
            case HealthHook::STATE_CRITICAL:
                $this->state = $this->STATE_CRITICAL;
                break;
            case HealthHook::STATE_UNKNOWN:
                $this->state = $this->STATE_UNKNOWN;
                break;
        }

        $this->title = $title;

        return $count;
    }
}
