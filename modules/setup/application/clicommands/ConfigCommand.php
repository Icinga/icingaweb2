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
     * Create Icinga Web 2's configuration directory
     *
     * USAGE:
     *
     *  icingacli setup config directory [options]
     *
     * OPTIONS:
     *
     *  --mode=<mode>           The access mode to use [2770]
     *
     *  --config=<directory>    Path to Icinga Web 2's configuration files [/etc/icingaweb2]
     *
     *  --group=<group>         Owner group for the configuration directory [icingaweb2]
     *
     * EXAMPLES:
     *
     *  icingacli setup config directory
     *
     *  icingacli setup config directory --mode 2775 --config /opt/icingaweb2/etc
     */
    public function directoryAction()
    {
        $group = trim($this->params->get('group', 'icingaweb2'));
        if (strlen($group) === 0) {
            $this->fail($this->translate(
                'The argument --group expects a owner group for the configuration directory'
            ));
        }

        $config = $this->params->get('config', $this->app->getConfigDir());
        if (file_exists($config)) {
            printf($this->translate("Configuration directory already exists at: %s\n"), $config);
            return true;
        }

        $mode = octdec($this->params->get('mode', '2770'));
        if (false === mkdir($config)) {
            $this->fail(sprintf($this->translate('Unable to create path: %s'), $config));
            return false;
        }

        $old = umask(0); // Prevent $mode from being mangled by the system's umask ($old)
        chmod($config, $mode);
        umask($old);

        if (chgrp($config, $group) === false) {
            $this->fail(sprintf($this->translate('Unable to change the group of "%s" to "%s".'), $config, $group));
            return false;
        }

        printf($this->translate("Successfully created configuration directory at: %s\n"), $config);
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
     *  --path=<urlpath>                    The URL path to Icinga Web 2 [/icingaweb2]
     *
     *  --root/--document-root=<directory>  The directory from which the webserver will serve files [/path/to/icingaweb2/public]
     *
     *  --config=<directory>                Path to Icinga Web 2's configuration files [/etc/icingaweb2]
     *
     *  --file=<filename>                   Write configuration to file [stdout]
     *
     * EXAMPLES:
     *
     *  icingacli setup config webserver apache
     *
     *  icingacli setup config webserver apache --path /icingaweb2 --document-root /usr/share/icingaweb2/public --config=/etc/icingaweb2
     *
     *  icingacli setup config webserver apache --file /etc/apache2/conf.d/icingaweb2.conf
     *
     *  icingacli setup config webserver nginx
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
