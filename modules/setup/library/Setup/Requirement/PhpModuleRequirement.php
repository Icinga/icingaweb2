<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Requirement;

use Icinga\Application\Platform;
use Icinga\Module\Setup\Requirement;

class PhpModuleRequirement extends Requirement
{
    public function getTitle()
    {
        $title = parent::getTitle();
        if ($title === $this->getAlias()) {
            if ($title === null) {
                $title = $this->getCondition();
            }

            return sprintf(mt('setup', 'PHP Module: %s'), $title);
        }

        return $title;
    }

    protected function evaluate()
    {
        $moduleName = $this->getCondition();
        if (Platform::extensionLoaded($moduleName)) {
            $this->setStateText(sprintf(
                mt('setup', 'The PHP module %s is available.'),
                $this->getAlias() ?: $moduleName
            ));
            return true;
        } else {
            $this->setStateText(sprintf(
                mt('setup', 'The PHP module %s is missing.'),
                $this->getAlias() ?: $moduleName
            ));
            return false;
        }
    }
}
