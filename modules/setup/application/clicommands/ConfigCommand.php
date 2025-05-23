<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Clicommands;

use Icinga\Application\Logger;
use Icinga\Cli\Command;
use Icinga\Exception\IcingaException;
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
     *  --config=<directory>    Path to Icinga Web 2's configuration files [/etc/icingaweb2]
     *
     *  --mode=<mode>           The access mode to use [2770]
     *
     *  --group=<group>         Owner group for the configuration directory [icingaweb2]
     *
     * EXAMPLES:
     *
     *  icingacli setup config directory
     *
     *  icingacli setup config directory --mode=2775 --config=/opt/icingaweb2/etc
     */
    public function directoryAction()
    {
        $configDir = trim($this->params->get('config', $this->app->getConfigDir()));
        if (strlen($configDir) === 0) {
            $this->fail($this->translate(
                'The argument --config expects a path to Icinga Web 2\'s configuration files'
            ));
        }

        $group = trim($this->params->get('group', 'icingaweb2'));
        if (strlen($group) === 0) {
            $this->fail($this->translate(
                'The argument --group expects a owner group for the configuration directory'
            ));
        }

        $mode = trim($this->params->get('mode', '2770'));
        if (strlen($mode) === 0) {
            $this->fail($this->translate(
                'The argument --mode expects an access mode for the configuration directory'
            ));
        }

        if (! file_exists($configDir) && ! @mkdir($configDir, 0755, true)) {
            $e = error_get_last();
            $this->fail(sprintf(
                $this->translate('Can\'t create configuration directory %s: %s'),
                $configDir,
                $e['message']
            ));
        }

        if (! @chmod($configDir, octdec($mode))) {
            $e = error_get_last();
            $this->fail(sprintf(
                $this->translate('Can\'t change the mode of the configuration directory to %s: %s'),
                $mode,
                $e['message']
            ));
        }

        if (! @chgrp($configDir, $group)) {
            $e = error_get_last();
            $this->fail(sprintf(
                $this->translate('Can\'t change the group of %s to %s: %s'),
                $configDir,
                $group,
                $e['message']
            ));
        }

        printf($this->translate('Successfully created configuration directory %s') . PHP_EOL, $configDir);
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
     *  --root|--document-root=<directory>  The directory from which the webserver will serve files
     *                                      [/path/to/icingaweb2/public]
     *
     *  --enable-fpm                        Enable FPM handler for Apache (Nginx is always enabled)
     *
     *  --fpm-url=<url>                     Address where to pass requests to FPM [127.0.0.1:9000]
     *
     *  --fpm-uri=<uri>                     Alias for --fpm-url
     *
     *  --fpm-socket-path=<socketpath>      Socket path where to pass requests to FPM, overrides --fpm-url
     *
     *  --config=<directory>                Path to Icinga Web 2's configuration files [/etc/icingaweb2]
     *
     *  --file=<filename>                   Write configuration to file [stdout]
     *
     * EXAMPLES:
     *
     *  icingacli setup config webserver apache
     *
     *  icingacli setup config webserver apache \
     *    --path=/icingaweb2 \
     *    --document-root=/usr/share/icingaweb2/public \
     *    --config=/etc/icingaweb2
     *
     *  icingacli setup config webserver apache \
     *    --file=/etc/apache2/conf.d/icingaweb2.conf
     *
     *  icingacli setup config webserver apache \
     *    --file=/etc/apache2/conf.d/icingaweb2.conf
     *    --fpm-url=localhost:9000
     *
     *  icingacli setup config webserver nginx \
     *    --root=/usr/share/icingaweb2/public \
     *    --fpm-socket-path=/var/run/php8.3-fpm.sock
     */
    public function webserverAction()
    {
        if (($type = $this->params->getStandalone()) === null) {
            $this->fail($this->translate('Argument type is mandatory.'));
        }

        $webserver = null;
        try {
            $webserver = Webserver::createInstance($type);
        } catch (ProgrammingError $e) {
            $this->fail($this->translate('Unknown type') . ': ' . $type);
        }
        $urlPath = trim($this->params->get('path', $webserver->getUrlPath()));
        if (strlen($urlPath) === 0) {
            $this->fail($this->translate('The argument --path expects a URL path'));
        }
        $documentRoot = trim(
            $this->params->get('root', $this->params->get('document-root', $webserver->getDocumentRoot()))
        );
        if (strlen($documentRoot) === 0) {
            $this->fail($this->translate(
                'The argument --root/--document-root expects a directory from which the webserver will serve files'
            ));
        }
        $configDir = trim($this->params->get('config', $webserver->getConfigDir()));
        if (strlen($configDir) === 0) {
            $this->fail($this->translate(
                'The argument --config expects a path to Icinga Web 2\'s configuration files'
            ));
        }

        $enableFpm = $this->params->shift('enable-fpm', $webserver->getEnableFpm());

        $fpmSocketPath = trim($this->params->get('fpm-socket-path', $webserver->getFpmSocketPath()));
        $fpmUrl = trim($this->params->get('fpm-url', $webserver->getFpmUrl()));
        if (empty($fpmUrl)) {
            $fpmUrl = trim($this->params->get('fpm-uri', $webserver->getFpmUrl()));
        }
        if (empty($fpmSocketPath) && empty($fpmUrl)) {
            $this->fail($this->translate(
                'One of the arguments --fpm-socket-path or --fpm-url must be set to pass requests to FPM'
            ));
        } elseif (!empty($fpmSocketPath) && !empty($fpmUrl)) {
            $this->fail($this->translate(
                'Only one of the arguments --fpm-socket-path or --fpm-url must be set to pass requests to FPM'
            ));
        }
        $webserver
            ->setDocumentRoot($documentRoot)
            ->setConfigDir($configDir)
            ->setUrlPath($urlPath)
            ->setEnableFpm($enableFpm)
            ->setFpmUrl($fpmUrl)
            ->setFpmSocketPath($fpmSocketPath);
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
