<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Module\Setup\Clicommands;

use Icinga\Application\Logger;
use Icinga\Cli\Command;
use Icinga\Exception\ProgrammingError;
use Icinga\Module\Setup\Webserver;

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
     *   --mode  The access mode to use. Default is: 2770
     *   --path  The path to the configuration directory. If omitted the default is used.
     *
     * EXAMPLES:
     *
     *   icingacli setup config createDirectory apache
     *   icingacli setup config createDirectory apache --mode 2775
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

        $mode = octdec($this->params->get('mode', '2770'));
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

    /**
     * Create webserver configuration
     *
     * USAGE:
     *
     *  icingacli setup config webserver <apache|nginx> [options]
     *
     * OPTIONS:
     *
     *  --path=<webpath>        Path for the web server, default /icingaweb
     *
     *  --publicPath=<wwwroot>  Path to htdocs system path
     *
     *  --file=<filename>       Write configuration to file
     *
     *
     * EXAMPLES:
     *
     * icingacli setup config webserver apache
     *
     * icingacli setup config webserver apache --path /icingaweb --publicPath /usr/share/icingaweb/public
     *
     * icingacli setup config webserver apache --file /etc/apache2/conf.d/icingaweb.conf
     *
     * icingacli setup config webserver nginx
     */
    public function webserverAction()
    {
        if (($type = $this->params->getStandalone()) === null) {
            $this->fail($this->translate('Argument type is mandatory.'));
        }
        try {
            $webserver = Webserver::createInstance($type);
        } catch (ProgrammingError $e) {
            $this->fail($this->translate('Unknown type') . ': ' . $type);
        }
        $webserver->setApp($this->app);
        if (($path = $this->params->get('path', '/icingaweb')) === null) {
            $this->fail($this->translate('argument --path is mandatory.'));
        }
        if (($publicPath = $this->params->get('publicPath', $webserver->getPublicPath())) === null) {
            $this->fail($this->translate('argument --publicPath is mandatory.'));
        }
        $webserver->setWebPath($path);
        $webserver->setPublicPath($publicPath);
        $config = $webserver->generate() . "\n";
        if (($file = $this->params->get('file')) !== null) {
            if (file_exists($file) === true) {
                $this->fail(sprintf($this->translate('File %s already exists. Please delete it first.'), $file));
            }
            Logger::info($this->translate('Write %s configuration to file: %s'), $type, $file);
            $re = file_put_contents($file, $config);
            if ($re === false) {
                $this->fail($this->translate('Could not write to file') . ': ' . $file);
            }
            Logger::info($this->translate('Successfully written %d bytes to file'), $re);
            return true;
        }
        printf("# Your %s configuration:\n", $type);
        echo $config;
        return true;
    }
}
