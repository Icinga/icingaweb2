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
     *  --path=<urlpath>                    The URL path to Icinga Web 2 [/icingaweb]
     *
     *  --root/--document-root=<directory>  The directory from which the webserver will serve files [./public]
     *
     *  --config=<directory>                Path to Icinga Web 2's configuration files [/etc/icingaweb]
     *
     *  --file=<filename>                   Write configuration to file [stdout]
     *
     *
     * EXAMPLES:
     *
     * icingacli setup config webserver apache
     *
     * icingacli setup config webserver apache --path /icingaweb --document-root /usr/share/icingaweb/public --config=/etc/icingaweb
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
        $urlPath = $this->params->get('path', $webserver->getUrlPath());
        if (! is_string($urlPath) || strlen(trim($urlPath)) === 0) {
            $this->fail($this->translate('The argument --path expects a URL path'));
        }
        $documentRoot = $this->params->get('root', $this->params->get('document-root', $webserver->getDocumentRoot()));
        if (! is_string($documentRoot) || strlen(trim($documentRoot)) === 0) {
            $this->fail($this->translate(
                'The argument --root/--document-root expects a directory from which the webserver will serve files'
            ));
        }
        $configDir = $this->params->get('config', $webserver->getConfigDir());
        if (! is_string($configDir) || strlen(trim($configDir)) === 0) {
            $this->fail($this->translate(
                'The argument --config expects a path to Icinga Web 2\'s configuration files'
            ));
        }
        $webserver
            ->setDocumentRoot($documentRoot)
            ->setConfigDir($configDir)
            ->setUrlPath($urlPath);
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
        echo $config;
        return true;
    }
}
