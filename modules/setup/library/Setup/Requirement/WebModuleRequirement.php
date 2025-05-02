<?php
/* Icinga Web 2 | (c) 2020 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Setup\Requirement;

use Icinga\Application\Icinga;
use Icinga\Module\Setup\Requirement;

class WebModuleRequirement extends Requirement
{
    protected function evaluate()
    {
        if (count($this->getCondition()) === 2) {
            list($name, $version) = $this->getCondition();
            $op = '=';
            if (is_string($version)
                && preg_match('/^([<>=]{1,2})\s*v?((?:[\d.]+)(?:.+)?)$/', $version, $match)) {
                $op = $match[1];
                $version = $match[2];
            }
        } else {
            list($name, $op, $version) = $this->getCondition();
        }

        $mm = Icinga::app()->getModuleManager();
        if (! $mm->hasInstalled($name)) {
            $this->setStateText(sprintf(mt('setup', '%s is not installed'), $this->getAlias()));
            return false;
        }

        $module = $mm->getModule($name, false);

        $moduleVersion = $module->getVersion();
        if ($moduleVersion[0] === 'v') {
            $moduleVersion = substr($moduleVersion, 1);
        }

        $this->setStateText(sprintf(mt('setup', '%s version: %s'), $this->getAlias(), $moduleVersion));
        return $version === true || $version === null || version_compare($moduleVersion, $version, $op);
    }
}
