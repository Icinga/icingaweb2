<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Web\Navigation\Renderer;

use Icinga\Application\Hook\HealthHook;

class HealthNavigationRenderer extends BadgeNavigationItemRenderer
{
    public function getCount()
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
                $this->state = self::STATE_WARNING;
                break;
            case HealthHook::STATE_CRITICAL:
                $this->state = self::STATE_CRITICAL;
                break;
            case HealthHook::STATE_UNKNOWN:
                $this->state = self::STATE_UNKNOWN;
                break;
        }

        $this->title = $title;

        return $count;
    }
}
