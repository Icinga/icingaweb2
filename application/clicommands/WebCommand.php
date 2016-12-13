<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Clicommands;

use Icinga\Application\Icinga;
use Icinga\Cli\Command;
use Icinga\Exception\IcingaException;

class WebCommand extends Command
{
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
        $documentRoot = $this->params->shift();
        $socket  = $this->params->shift();

        // TODO: Sanity check!!
        if ($socket === null) {
            $socket = $this->Config()->get('standalone','listen','0.0.0.0:80');
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
        } else if ($pid) {
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
