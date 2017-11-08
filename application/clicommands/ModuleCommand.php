<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Clicommands;

use Icinga\Application\Logger;
use Icinga\Application\Modules\Manager;
use Icinga\Cli\Command;

/**
 * List and handle modules
 *
 * The module command allows you to handle your IcingaWeb modules
 *
 * Usage: icingacli module [<action>] [<modulename>]
 */
class ModuleCommand extends Command
{
    /**
     * @var Manager
     */
    protected $modules;

    public function init()
    {
        $this->modules = $this->app->getModuleManager();
    }

    /**
     * List all enabled modules
     *
     * If you are interested in all installed modules pass 'installed' (or
     * even --installed) as a command parameter. If you enable --verbose even
     * more details will be shown
     *
     * Usage: icingacli module list [installed] [--verbose]
     */
    public function listAction()
    {
        if ($type = $this->params->shift()) {
            if (! in_array($type, array('enabled', 'installed'))) {
                return $this->showUsage();
            }
        } else {
            $type = 'enabled';
            $this->params->shift('enabled');
            if ($this->params->shift('installed')) {
                $type = 'installed';
            }
        }

        if ($this->hasRemainingParams()) {
            return $this->showUsage();
        }

        if ($type === 'enabled') {
            $modules = $this->modules->listEnabledModules();
        } else {
            $modules = $this->modules->listInstalledModules();
        }
        if (empty($modules)) {
            echo "There are no $type modules\n";
            return;
        }
        if ($this->isVerbose) {
            printf("%-14s %-9s %-9s DIRECTORY\n", 'MODULE', 'VERSION', 'STATE');
        } else {
            printf("%-14s %-9s %-9s %s\n", 'MODULE', 'VERSION', 'STATE', 'DESCRIPTION');
        }
        foreach ($modules as $module) {
            $mod = $this->modules->loadModule($module)->getModule($module);
            if ($this->isVerbose) {
                $dir = ' ' . $this->modules->getModuleDir($module);
            } else {
                $dir = $mod->getTitle();
            }
            printf(
                "%-14s %-9s %-9s %s\n",
                $module,
                $mod->getVersion(),
                ($type === 'enabled' || $this->modules->hasEnabled($module))
                    ? $this->modules->hasInstalled($module) ? 'enabled' : 'dangling'
                    : 'disabled',
                $dir
            );
        }
        echo "\n";
    }

    /**
     * Enable a given module
     *
     * Usage: icingacli module enable <module-name>
     */
    public function enableAction()
    {
        if (! $module = $this->params->shift()) {
            $module = $this->params->shift('module');
        }
        if (! $module || $this->hasRemainingParams()) {
            return $this->showUsage();
        }
        $this->modules->enableModule($module);
    }

    /**
     * Disable a given module
     *
     * Usage: icingacli module disable <module-name>
     */
    public function disableAction()
    {
        if (! $module = $this->params->shift()) {
            $module = $this->params->shift('module');
        }
        if (! $module || $this->hasRemainingParams()) {
            return $this->showUsage();
        }

        if ($this->modules->hasEnabled($module)) {
            $this->modules->disableModule($module);
        } else {
            Logger::info('Module "%s" is already disabled', $module);
        }
    }

    /**
     * Show all restrictions provided by your modules
     *
     * Asks each enabled module for all available restriction names and
     * descriptions and shows a quick overview
     *
     * Usage: icingacli module restrictions
     */
    public function restrictionsAction()
    {
        printf("%-14s %-16s %s\n", 'MODULE', 'RESTRICTION', 'DESCRIPTION');
        foreach ($this->modules->listEnabledModules() as $moduleName) {
            $module = $this->modules->loadModule($moduleName)->getModule($moduleName);
            foreach ($module->getProvidedRestrictions() as $restriction) {
                printf(
                    "%-14s %-16s %s\n",
                    $moduleName,
                    $restriction->name,
                    $restriction->description
                );
            }
        }
    }

    /**
     * Show all permissions provided by your modules
     *
     * Asks each enabled module for it's available permission names and
     * descriptions and shows a quick overview
     *
     * Usage: icingacli module permissions
     */
    public function permissionsAction()
    {
        printf("%-14s %-24s %s\n", 'MODULE', 'PERMISSION', 'DESCRIPTION');
        foreach ($this->modules->listEnabledModules() as $moduleName) {
            $module = $this->modules->loadModule($moduleName)->getModule($moduleName);
            foreach ($module->getProvidedPermissions() as $restriction) {
                printf(
                    "%-14s %-24s %s\n",
                    $moduleName,
                    $restriction->name,
                    $restriction->description
                );
            }
        }
    }

    /**
     * Search for a given module
     *
     * Does a lookup against your configured IcingaWeb app stores and tries to
     * find modules matching your search string
     *
     * Usage: icingacli module search <search-string>
     */
    public function searchAction()
    {
        $this->fail("Not implemented yet");
    }

    /**
     * Install a given module
     *
     * Downloads a given module or installes a module from a given archive
     *
     * Usage: icingacli module install <module-name>
     *        icingacli module install </path/to/archive.tar.gz>
     */
    public function installAction()
    {
        $this->fail("Not implemented yet");
    }

    /**
     * Remove a given module
     *
     * Removes the given module from your disk. Module configuration will be
     * preserved
     *
     * Usage: icingacli module remove <module-name>
     */
    public function removeAction()
    {
        $this->fail("Not implemented yet");
    }

    /**
     * Purge a given module
     *
     * Removes the given module from your disk. Also wipes configuration files
     * and other data stored and/or generated by this module
     *
     * Usage: icingacli module remove <module-name>
     */
    public function purgeAction()
    {
        $this->fail("Not implemented yet");
    }
}
