<?php
/* Icinga Web 2 | (c) 2019 Icinga Development Team | GPLv2+ */

namespace Icinga\Clicommands;

use Icinga\Application\Version;
use Icinga\Application\Icinga;
use Icinga\Cli\Loader;
use Icinga\Cli\Command;

/**
 * Shows version of Icinga Web 2, loaded modules and PHP
 *
 * The version command shows version numbers for Icinga Web 2, loaded modules and PHP.
 *
 * Usage: icingacli --version
 */
class VersionCommand extends Command
{
    protected $defaultActionName = 'show';

    /**
     * Shows version of Icinga Web 2, loaded modules and PHP
     *
     * The version command shows version numbers for Icinga Web 2, loaded modules and PHP.
     *
     * Usage: icingacli --version
     */
    public function showAction()
    {
        $getVersion = Version::get();
        printf("%-12s  %-9s \n", 'Icinga Web 2', $getVersion['appVersion']);

        if (isset($getVersion['gitCommitID'])) {
            printf("%-12s  %-9s \n", 'Git Commit', $getVersion['gitCommitID']);
        }

        printf("%-12s  %-9s \n", 'PHP Version', PHP_VERSION);

        $modules = Icinga::app()->getModuleManager()->loadEnabledModules()->getLoadedModules();

        $maxLength = 0;
        foreach ($modules as $module) {
            $length = strlen($module->getName());
            if ($length > $maxLength) {
                $maxLength = $length;
            }
        }

        printf("%-{$maxLength}s  %-9s \n", 'MODULE', 'VERSION');
        foreach ($modules as $module) {
            printf("%-{$maxLength}s  %-9s \n", $module->getName(), $module->getVersion());
        }
    }
}
