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

        // TODO: Store webserver log, switch uid, log index.php includes, pid file
        pcntl_exec(
            readlink('/proc/self/exe'),
            ['-S', $socket, '-t', $documentRoot, Icinga::app()->getLibraryDir('/Icinga/Application/webrouter.php')]
        );
    }

    public function stopAction()
    {
        // TODO: No, that's NOT what we want
        $prog = readlink('/proc/self/exe');
        shell_exec("killall $prog");
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

            // Replace console with /dev/null by first freeing the (lowest possible) FDs 0, 1 and 2
            // and then opening /dev/null once for every one of them (open(2) chooses the lowest free FD).

            fclose(STDIN);
            fclose(STDOUT);
            fclose(STDERR);

            fopen('/dev/null', 'rb');
            fopen('/dev/null', 'wb');
            fopen('/dev/null', 'wb');
        }
    }
}
