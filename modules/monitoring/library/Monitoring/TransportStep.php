<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring;

use Exception;
use Icinga\Exception\ProgrammingError;
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
        switch ($this->data['transportConfig']['transport']) {
            case 'local':
                $details = '<p>' . sprintf(
                    mt(
                        'monitoring',
                        'Icinga Web 2 will use the named pipe located at "%s"'
                            . ' to send commands to your monitoring instance.'
                    ),
                    $this->data['transportConfig']['path']
                ) . '</p>';
                break;
            case 'remote':
                $details = '<p>'
                    . sprintf(
                        mt(
                            'monitoring',
                            'Icinga Web 2 will use the named pipe located on a remote machine at "%s" to send commands'
                                . ' to your monitoring instance by using the connection details listed below:'
                        ),
                        $this->data['transportConfig']['path']
                    )
                    . '</p>'
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
                break;
            case 'api':
                $details = '<p>'
                    . mt(
                        'monitoring',
                        'Icinga Web 2 will use the Icinga 2 API to send commands'
                            . ' to your monitoring instance by using the connection details listed below:'
                    )
                    . '</p>'
                    . '<table>'
                    . '<tbody>'
                    . '<tr>'
                    . '<td><strong>' . mt('monitoring', 'Host') . '</strong></td>'
                    . '<td>' . $this->data['transportConfig']['host'] . '</td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td><strong>' . mt('monitoring', 'Port') . '</strong></td>'
                    . '<td>' . $this->data['transportConfig']['port'] . '</td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td><strong>' . mt('monitoring', 'Username') . '</strong></td>'
                    . '<td>' . $this->data['transportConfig']['username'] . '</td>'
                    . '</tr>'
                    . '<tr>'
                    . '<td><strong>' . mt('monitoring', 'Password') . '</strong></td>'
                    . '<td>' . str_repeat('*', strlen($this->data['transportConfig']['password'])) . '</td>'
                    . '</tr>'
                    . '</tbody>'
                    . '</table>';
                break;
            default:
                throw new ProgrammingError(
                    'Unknown command transport type: %s',
                    $this->data['transportConfig']['transport']
                );
        }

        return '<h2>' . mt('monitoring', 'Command Transport', 'setup.page.title') . '</h2>'
            . '<div class="topic">' . $details . '</div>';
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
