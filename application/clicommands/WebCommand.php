<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Clicommands;

use Icinga\Cli\Command;
use Exception;

class WebCommand extends Command
{
    public function serveAction()
    {
        $minVersion = '5.4.0';
        if (version_compare(PHP_VERSION, $minVersion) < 0) {
            throw new Exception(sprintf(
                'You are running PHP %s, internal webserver requires %s.',
                PHP_VERSION,
                $minVersion
            ));
        }

        $fork = $this->params->get('daemonize');
        $basedir = $this->params->shift();
        $socket  = $this->params->shift();

        // TODO: Sanity check!!
        if ($socket === null) {
            $socket = '0.0.0.0:80';
            // throw new Exception('Socket is required');
        }
        if ($basedir === null) {
            $basedir = dirname(ICINGAWEB_APPDIR) . '/public';
            if (! file_exists($basedir) || ! is_dir($basedir)) {
                throw new Exception('Basedir is required');
            }
        }
        $basedir = realpath($basedir);

        if ($fork) {
            $this->forkAndExit();
        }
        echo "Serving Icingaweb from $basedir\n";
        $cmd = sprintf(
            '%s -S %s -t %s %s',
            readlink('/proc/self/exe'),
            $socket,
            $basedir,
            ICINGA_LIBDIR . '/Icinga/Application/webrouter.php'
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
             throw new Exception('Could not fork');
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
