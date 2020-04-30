<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Setup\Requirement;

use Icinga\Application\Icinga;
use Icinga\Module\Setup\Requirement;

class WebModuleRequirement extends Requirement
{
    protected function evaluate()
    {
        list($name, $op, $version) = $this->getCondition();

        $mm = Icinga::app()->getModuleManager();
        if (! $mm->hasInstalled($name)) {
            $this->setStateText(sprintf(mt('setup', '%s is not installed'), $this->getAlias()));
            return false;
        }

        $module = $mm->getModule($name, false);
        $this->setStateText(sprintf(mt('setup', '%s version: %s'), $this->getAlias(), $module->getVersion()));
        return version_compare($module->getVersion(), $version, $op);
    }
}
