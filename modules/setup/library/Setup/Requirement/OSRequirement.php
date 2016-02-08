<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Requirement;

use Icinga\Application\Platform;
use Icinga\Module\Setup\Requirement;

class OSRequirement extends Requirement
{
    public function getTitle()
    {
        $title = parent::getTitle();
        if ($title === null) {
            return sprintf(mt('setup', '%s Platform'), ucfirst($this->getCondition()));
        }

        return $title;
    }

    protected function evaluate()
    {
        $phpOS = Platform::getOperatingSystemName();
        $this->setStateText(sprintf(mt('setup', 'You are running PHP on a %s system.'), ucfirst($phpOS)));
        return strtolower($phpOS) === strtolower($this->getCondition());
    }
}
