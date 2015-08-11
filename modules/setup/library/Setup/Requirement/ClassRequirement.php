<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Requirement;

use Icinga\Application\Platform;
use Icinga\Module\Setup\Requirement;

class ClassRequirement extends Requirement
{
    protected function evaluate()
    {
        $classNameOrPath = $this->getCondition();
        if (Platform::classExists($classNameOrPath)) {
            $this->setStateText(sprintf(
                mt('setup', 'The %s is available.', 'setup.requirement.class'),
                $this->getAlias() ?: $classNameOrPath . ' ' . mt('setup', 'class', 'setup.requirement.class')
            ));
            return true;
        } else {
            $this->setStateText(sprintf(
                mt('setup', 'The %s is missing.', 'setup.requirement.class'),
                $this->getAlias() ?: $classNameOrPath . ' ' . mt('setup', 'class', 'setup.requirement.class')
            ));
            return false;
        }
    }
}
