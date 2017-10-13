<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Requirement;

use Icinga\Module\Setup\Requirement;

class DirectoryRequirement extends Requirement
{
    public function getTitle()
    {
        $title = parent::getTitle();
        if ($title === null) {
            return sprintf(mt('setup', 'Read- and writable directory %s'), var_export($this->getCondition(), true));
        }

        return $title;
    }

    protected function evaluate()
    {
        $dir = $this->getCondition();

        switch (false) {
            case file_exists($dir):
                $this->setStateText(sprintf(mt('setup', 'The directory %s does not exist.'), var_export($dir, true)));
                return false;

            case is_readable($dir):
                $this->setStateText(sprintf(mt('setup', 'The directory %s is not readable.'), var_export($dir, true)));
                return false;

            case is_writable($dir):
                $this->setStateText(sprintf(mt('setup', 'The directory %s is not writable.'), var_export($dir, true)));
                return false;

            default:
                $this->setStateText(
                    sprintf(mt('setup', 'The directory %s is read- and writable.'), var_export($dir, true))
                );
                return true;
        }
    }
}
