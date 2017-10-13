<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Clicommands;

use Icinga\Cli\Command;

class StorageCommand extends Command
{
    /**
     * Create Icinga Web 2's storage directory
     *
     * USAGE:
     *
     *  icingacli setup storage directory [options]
     *
     * OPTIONS:
     *
     *  --storage=<directory>   Path to Icinga Web 2's non-configuration files [/var/lib/icingaweb2]
     *
     *  --mode=<mode>           The access mode to use [2770]
     *
     *  --group=<group>         Owner group for the storage directory [icingaweb2]
     *
     * EXAMPLES:
     *
     *  icingacli setup storage directory
     *
     *  icingacli setup storage directory --mode=2775 --storage=/opt/icingaweb2/var/lib
     */
    public function directoryAction()
    {
        $storageDir = trim($this->params->get('storage', $this->app->getStorageDir()));
        if (strlen($storageDir) === 0) {
            $this->fail($this->translate(
                'The argument --storage expects a path to Icinga Web 2\'s non-configuration files'
            ));
        }

        $group = trim($this->params->get('group', 'icingaweb2'));
        if (strlen($group) === 0) {
            $this->fail($this->translate(
                'The argument --group expects a owner group for the storage directory'
            ));
        }

        $mode = trim($this->params->get('mode', '2770'));
        if (strlen($mode) === 0) {
            $this->fail($this->translate(
                'The argument --mode expects an access mode for the storage directory'
            ));
        }

        if (! file_exists($storageDir) && ! @mkdir($storageDir, 0755, true)) {
            $e = error_get_last();
            $this->fail(sprintf(
                $this->translate('Can\'t create storage directory %s: %s'),
                $storageDir,
                $e['message']
            ));
        }

        if (! @chmod($storageDir, octdec($mode))) {
            $e = error_get_last();
            $this->fail(sprintf(
                $this->translate('Can\'t change the mode of the storage directory to %s: %s'),
                $mode,
                $e['message']
            ));
        }

        if (! @chgrp($storageDir, $group)) {
            $e = error_get_last();
            $this->fail(sprintf(
                $this->translate('Can\'t change the group of %s to %s: %s'),
                $storageDir,
                $group,
                $e['message']
            ));
        }

        printf($this->translate('Successfully created storage directory %s') . PHP_EOL, $storageDir);
    }
}
