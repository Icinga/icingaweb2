<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring;

use Exception;
use Icinga\Module\Setup\Step;
use Icinga\Application\Config;

class InstanceStep extends Step
{
    protected $data;

    protected $error;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function apply()
    {
        $instanceConfig = $this->data['instanceConfig'];
        $instanceName = $instanceConfig['name'];
        unset($instanceConfig['name']);

        try {
            Config::fromArray(array($instanceName => $instanceConfig))
                ->setConfigFile(Config::resolvePath('modules/monitoring/instances.ini'))
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
        $pageTitle = '<h2>' . mt('monitoring', 'Monitoring Instance', 'setup.page.title') . '</h2>';

        if (isset($this->data['instanceConfig']['host'])) {
            $pipeHtml = '<p>' . sprintf(
                mt(
                    'monitoring',
                    'Icinga Web 2 will use the named pipe located on a remote machine at "%s" to send commands'
                    . ' to your monitoring instance by using the connection details listed below:'
                ),
                $this->data['instanceConfig']['path']
            ) . '</p>';

            $pipeHtml .= ''
                . '<table>'
                . '<tbody>'
                . '<tr>'
                . '<td><strong>' . mt('monitoring', 'Remote Host') . '</strong></td>'
                . '<td>' . $this->data['instanceConfig']['host'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . mt('monitoring', 'Remote SSH Port') . '</strong></td>'
                . '<td>' . $this->data['instanceConfig']['port'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . mt('monitoring', 'Remote SSH User') . '</strong></td>'
                . '<td>' . $this->data['instanceConfig']['user'] . '</td>'
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
                $this->data['instanceConfig']['path']
            ) . '</p>';
        }

        return $pageTitle . '<div class="topic">' . $pipeHtml . '</div>';
    }

    public function getReport()
    {
        if ($this->error === false) {
            $message = mt('monitoring', 'Monitoring instance configuration has been successfully created: %s');
            return '<p>' . sprintf($message, Config::resolvePath('modules/monitoring/instances.ini')) . '</p>';
        } elseif ($this->error !== null) {
            $message = mt(
                'monitoring',
                'Monitoring instance configuration could not be written to: %s; An error occured:'
            );
            return '<p class="error">' . sprintf($message, Config::resolvePath('modules/monitoring/instances.ini'))
                . '</p><p>' . $this->error->getMessage() . '</p>';
        }
    }
}
