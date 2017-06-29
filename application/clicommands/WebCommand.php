<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Clicommands;

use Icinga\Application\Icinga;
use Icinga\Cli\Command;
use Icinga\Exception\IcingaException;

class WebCommand extends Command
{
    /**
     * Serve Icinga Web 2 with PHP's built-in web server
     *
     * USAGE
     *
     *   icingacli web serve [options] [<document-root>]
     *
     * OPTIONS
     *
     *   --daemonize            Run in background
     *   --port=<port>          The port to listen on
     *   --listen=<host:port>   The address to listen on
     *   <document-root>        The document root directory of Icinga Web 2 (e.g. ./public)
     *
     * EXAMPLES
     *
     *   icingacli web serve --port=8080
     *   icingacli web serve --listen=127.0.0.1:8080 ./public
     */
    public function serveAction()
    {
        $minVersion = '5.4.0';
        if (version_compare(PHP_VERSION, $minVersion) < 0) {
            throw new IcingaException(
                'You are running PHP %s, internal webserver requires %s.',
                PHP_VERSION,
                $minVersion
            );
        }

        $fork = $this->params->get('daemonize');
        $listen = $this->params->get('listen');
        $port = $this->params->get('port');
        $documentRoot = $this->params->shift();
        if ($listen === null) {
            $socket = $port === null ? $this->params->shift() : '0.0.0.0:' . $port;
        } else {
            $socket = $listen;
        }

        if ($socket === null) {
            $socket = $this->Config()->get('standalone', 'listen', '0.0.0.0:80');
        }
        if ($documentRoot === null) {
            $documentRoot = Icinga::app()->getBaseDir('public');
            if (! file_exists($documentRoot) || ! is_dir($documentRoot)) {
                throw new IcingaException('Document root directory is required');
            }
        }
        $documentRoot = realpath($documentRoot);

        if ($fork) {
            $this->forkAndExit();
        }
        echo "Serving Icinga Web 2 from directory $documentRoot and listening on $socket\n";
        $cmd = sprintf(
            '%s -S %s -t %s %s',
            readlink('/proc/self/exe'),
            $socket,
            $documentRoot,
            Icinga::app()->getLibraryDir('/Icinga/Application/webrouter.php')
        );

        // TODO: Store webserver log, switch uid, log index.php includes, pid file
        if ($fork) {
            exec($cmd);
        } else {
            passthru($cmd);
        }
    }

    public function stopAction()
    {
        // TODO: No, that's NOT what we want
        $prog = readlink('/proc/self/exe');
        `killall $prog`;
    }

    protected function forkAndExit()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
             throw new IcingaException('Could not fork');
        } elseif ($pid) {
            echo $this->screen->colorize('[OK]')
               . " Icinga Web server forked successfully\n";
            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);
            exit;
            // pcntl_wait($status);
        } else {
             // child
        }
    }
}
