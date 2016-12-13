<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Requirement;

use Icinga\Application\Platform;
use Icinga\Module\Setup\Requirement;

class ClassRequirement extends Requirement
{
    protected function evaluate()
    {
        return Platform::classExists($this->getCondition());
    }

    /**
     * {@inheritdoc}
     */
    public function getStateText()
    {
        $stateText = parent::getStateText();
        if ($stateText === null) {
            $alias = $this->getAlias();
            if ($this->getState()) {
                $stateText = $alias === null
                    ? sprintf(mt('setup', 'The %s class is available.', 'setup.requirement.class'), $this->getCondition())
                    : sprintf(mt('setup', 'The %s is available.', 'setup.requirement.class'), $alias);
            } else {
                $stateText = $alias === null
                    ? sprintf(mt('setup', 'The %s class is missing.', 'setup.requirement.class'), $this->getCondition())
                    : sprintf(mt('setup', 'The %s is missing.', 'setup.requirement.class'), $alias);
            }
        }
        return $stateText;
    }
}
