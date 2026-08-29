<?php

namespace Icinga\Module\Setup\Requirement;

use Icinga\Module\Setup\Requirement;

class ModuleMissingRequirement extends Requirement
{
    protected function evaluate()
    {
        $this->setStateText(sprintf(
            mt('setup', 'Module %s is not chosen.'),
            $this->getAlias()
        ));

        return false;
    }

    public function equals(Requirement $requirement)
    {
        return false;
    }
}
