<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Requirement;

use Icinga\Application\Platform;
use Icinga\Module\Setup\Requirement;

class PhpConfigRequirement extends Requirement
{
    protected function evaluate()
    {
        list($configDirective, $value) = $this->getCondition();
        $configValue = Platform::getPhpConfig($configDirective);
        $this->setStateText(
            $configValue
                ? sprintf(mt('setup', 'The PHP config `%s\' is set to "%s".'), $configDirective, $configValue)
                : sprintf(mt('setup', 'The PHP config `%s\' is not defined.'), $configDirective)
        );
        return is_bool($value) ? $configValue == $value : $configValue === $value;
    }
}
