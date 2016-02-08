<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring;

use Exception;
use Icinga\Module\Setup\Step;
use Icinga\Application\Config;
use Icinga\Exception\IcingaException;

class TransportStep extends Step
{
    protected $data;

    protected $error;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function apply()
    {
        $transportConfig = $this->data['transportConfig'];
        $transportName = $transportConfig['name'];
        unset($transportConfig['name']);

        try {
            Config::fromArray(array($transportName => $transportConfig))
                ->setConfigFile(Config::resolvePath('modules/monitoring/commandtransports.ini'))
                ->saveIni();
        } catch (Exception $e) {
            $this->error = $e;
            return false;
        }

        $this->error = false;
        return true;
    }

    public function getSummary()
    {
        $pageTitle = '<h2>' . mt('monitoring', 'Command Transport', 'setup.page.title') . '</h2>';

        if (isset($this->data['transportConfig']['host'])) {
            $pipeHtml = '<p>' . sprintf(
                mt(
                    'monitoring',
                    'Icinga Web 2 will use the named pipe located on a remote machine at "%s" to send commands'
                    . ' to your monitoring instance by using the connection details listed below:'
                ),
                $this->data['transportConfig']['path']
            ) . '</p>';

            $pipeHtml .= ''
                . '<table>'
                . '<tbody>'
                . '<tr>'
                . '<td><strong>' . mt('monitoring', 'Remote Host') . '</strong></td>'
                . '<td>' . $this->data['transportConfig']['host'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . mt('monitoring', 'Remote SSH Port') . '</strong></td>'
                . '<td>' . $this->data['transportConfig']['port'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . mt('monitoring', 'Remote SSH User') . '</strong></td>'
                . '<td>' . $this->data['transportConfig']['user'] . '</td>'
                . '</tr>'
                . '</tbody>'
                . '</table>';
        } else {
            $pipeHtml = '<p>' . sprintf(
                mt(
                    'monitoring',
                    'Icinga Web 2 will use the named pipe located at "%s"'
                    . ' to send commands to your monitoring instance.'
                ),
                $this->data['transportConfig']['path']
            ) . '</p>';
        }

        return $pageTitle . '<div class="topic">' . $pipeHtml . '</div>';
    }

    public function getReport()
    {
        if ($this->error === false) {
            return array(sprintf(
                mt('monitoring', 'Command transport configuration has been successfully created: %s'),
                Config::resolvePath('modules/monitoring/commandtransports.ini')
            ));
        } elseif ($this->error !== null) {
            return array(
                sprintf(
                    mt(
                        'monitoring',
                        'Command transport configuration could not be written to: %s. An error occured:'
                    ),
                    Config::resolvePath('modules/monitoring/commandtransports.ini')
                ),
                sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->error))
            );
        }
    }
}
