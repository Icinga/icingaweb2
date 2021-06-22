<?php
/* Icinga Web 2 | (c) 2021 Icinga GmbH | GPLv2+ */

namespace Icinga\Module\Setup\Requirement;

use Icinga\Application\Icinga;
use Icinga\Module\Setup\Requirement;

class WebLibraryRequirement extends Requirement
{
    protected function evaluate()
    {
        list($name, $op, $version) = $this->getCondition();

        $libs = Icinga::app()->getLibraries();
        if (! $libs->has($name)) {
            $this->setStateText(sprintf(mt('setup', '%s is not installed'), $this->getAlias()));
            return false;
        }

        $this->setStateText(sprintf(mt('setup', '%s version: %s'), $this->getAlias(), $libs->get($name)->getVersion()));
        return $libs->has($name, $op . $version);
    }
}
