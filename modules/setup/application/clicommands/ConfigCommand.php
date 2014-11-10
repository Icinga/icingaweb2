<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup\Clicommands;

use Icinga\Cli\Command;

class ConfigCommand extends Command
{
    /**
     * Create the configuration directory
     *
     * This command creates the configuration directory for Icinga Web 2. The `group' argument
     * is mandatory and should be the groupname of the user your web server is running as.
     *
     * USAGE:
     *
     *   icingacli setup config createDirectory <group> [options]
     *
     * OPTIONS:
     *
     *   --mode  The access mode to use. Default is: 2775
     *   --path  The path to the configuration directory. If omitted the default is used.
     *
     * EXAMPLES:
     *
     *   icingacli setup config createDirectory apache
     *   icingacli setup config createDirectory apache --mode 2770
     *   icingacli setup config createDirectory apache --path /some/path
     */
    public function createDirectoryAction()
    {
        $group = $this->params->getStandalone();
        if ($group === null) {
            $this->fail($this->translate('The `group\' argument is mandatory.'));
            return false;
        }

        $path = $this->params->get('path', $this->app->getConfigDir());
        if (file_exists($path)) {
            printf($this->translate("Configuration directory already exists at: %s\n"), $path);
            return true;
        }

        $mode = octdec($this->params->get('mode', '2775'));
        if (false === mkdir($path)) {
            $this->fail(sprintf($this->translate('Unable to create path: %s'), $path));
            return false;
        }

        $old = umask(0); // Prevent $mode from being mangled by the system's umask ($old)
        chmod($path, $mode);
        umask($old);

        if (chgrp($path, $group) === false) {
            $this->fail(sprintf($this->translate('Unable to change the group of "%s" to "%s".'), $path, $group));
            return false;
        }

        printf($this->translate("Successfully created configuration directory at: %s\n"), $path);
    }
}
