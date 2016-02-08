<?php
/* Icinga Web 2 | (c) 2013 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring\Clicommands;

use Icinga\Protocol\Nrpe\Connection;
use Icinga\Cli\Command;
use Exception;

/**
 * NRPE
 */
class NrpeCommand extends Command
{
    protected $defaultActionName = 'check';

    /**
     * Execute an NRPE command
     *
     * This command will execute an NRPE check, fire it against the given host
     * and also pass through all your parameters. Output will be shown, exit
     * code respected.
     *
     * USAGE
     *
     * icingacli monitoring nrpe <host> <command> [--ssl] [nrpe options]
     *
     * EXAMPLE
     *
     * icingacli monitoring nrpe 127.0.0.1 CheckMEM --ssl --MaxWarn=80% \
     *   --MaxCrit=90% --type=physical
     */
    public function checkAction()
    {
        $host = $this->params->shift();
        if (! $host) {
            echo $this->showUsage();
            exit(3);
        }
        $command = $this->params->shift(null, '_NRPE_CHECK');
        $port = $this->params->shift('port', 5666);
        try {
            $nrpe = new Connection($host, $port);
            if ($this->params->shift('ssl')) {
                $nrpe->useSsl();
            }
            $args = array();
            foreach ($this->params->getParams() as $k => $v) {
                $args[] = $k . '=' . $v;
            }
            echo $nrpe->sendCommand($command, $args) . "\n";
            exit($nrpe->getLastReturnCode());
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(3);
        }
    }
}

