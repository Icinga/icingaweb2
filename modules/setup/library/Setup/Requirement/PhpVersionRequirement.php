<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Requirement;

use Icinga\Application\Platform;
use Icinga\Module\Setup\Requirement;

class PhpVersionRequirement extends Requirement
{
    public function getTitle()
    {
        $title = parent::getTitle();
        if ($title === null) {
            return mt('setup', 'PHP Version');
        }

        return $title;
    }

    protected function evaluate()
    {
        $phpVersion = Platform::getPhpVersion();
        $this->setStateText(sprintf(mt('setup', 'You are running PHP version %s.'), $phpVersion));
        list($operator, $requiredVersion) = $this->getCondition();
        return version_compare($phpVersion, $requiredVersion, $operator);
    }
}
