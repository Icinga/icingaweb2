<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring;

use Exception;
use Icinga\Module\Setup\Step;
use Icinga\Application\Config;

class BackendStep extends Step
{
    protected $data;

    protected $backendIniError;

    protected $resourcesIniError;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function apply()
    {
        $success = $this->createBackendsIni();
        $success &= $this->createResourcesIni();
        return $success;
    }

    protected function createBackendsIni()
    {
        $config = array();
        $config[$this->data['backendConfig']['name']] = array(
            'type'      => $this->data['backendConfig']['type'],
            'resource'  => $this->data['resourceConfig']['name']
        );

        try {
            Config::fromArray($config)
                ->setConfigFile(Config::resolvePath('modules/monitoring/backends.ini'))
                ->saveIni();
        } catch (Exception $e) {
            $this->backendIniError = $e;
            return false;
        }

        $this->backendIniError = false;
        return true;
    }

    protected function createResourcesIni()
    {
        $resourceConfig = $this->data['resourceConfig'];
        $resourceName = $resourceConfig['name'];
        unset($resourceConfig['name']);

        try {
            $config = Config::app('resources', true);
            $config->setSection($resourceName, $resourceConfig);
            $config->saveIni();
        } catch (Exception $e) {
            $this->resourcesIniError = $e;
            return false;
        }

        $this->resourcesIniError = false;
        return true;
    }

    public function getSummary()
    {
        $pageTitle = '<h2>' . mt('monitoring', 'Monitoring Backend', 'setup.page.title') . '</h2>';
        $backendDescription = '<p>' . sprintf(
            mt(
                'monitoring',
                'Icinga Web 2 will retrieve information from your monitoring environment'
                . ' using a backend called "%s" and the specified resource below:'
            ),
            $this->data['backendConfig']['name']
        ) . '</p>';

        if ($this->data['resourceConfig']['type'] === 'db') {
            $resourceTitle = '<h3>' . mt('monitoring', 'Database Resource') . '</h3>';
            $resourceHtml = ''
                . '<table>'
                . '<tbody>'
                . '<tr>'
                . '<td><strong>' . t('Resource Name') . '</strong></td>'
                . '<td>' . $this->data['resourceConfig']['name'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . t('Database Type') . '</strong></td>'
                . '<td>' . $this->data['resourceConfig']['db'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . t('Host') . '</strong></td>'
                . '<td>' . $this->data['resourceConfig']['host'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . t('Port') . '</strong></td>'
                . '<td>' . $this->data['resourceConfig']['port'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . t('Database Name') . '</strong></td>'
                . '<td>' . $this->data['resourceConfig']['dbname'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . t('Username') . '</strong></td>'
                . '<td>' . $this->data['resourceConfig']['username'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . t('Password') . '</strong></td>'
                . '<td>' . str_repeat('*', strlen($this->data['resourceConfig']['password'])) . '</td>'
                . '</tr>'
                . '</tbody>'
                . '</table>';
        } else { // $this->data['resourceConfig']['type'] === 'livestatus'
            $resourceTitle = '<h3>' . mt('monitoring', 'Livestatus Resource') . '</h3>';
            $resourceHtml = ''
                . '<table>'
                . '<tbody>'
                . '<tr>'
                . '<td><strong>' . t('Resource Name') . '</strong></td>'
                . '<td>' . $this->data['resourceConfig']['name'] . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . t('Socket') . '</strong></td>'
                . '<td>' . $this->data['resourceConfig']['socket'] . '</td>'
                . '</tr>'
                . '</tbody>'
                . '</table>';
        }

        return $pageTitle . '<div class="topic">' . $backendDescription . $resourceTitle . $resourceHtml . '</div>';
    }

    public function getReport()
    {
        $report = '';
        if ($this->backendIniError === false) {
            $message = mt('monitoring', 'Monitoring backend configuration has been successfully written to: %s');
            $report .= '<p>' . sprintf($message, Config::resolvePath('modules/monitoring/backends.ini')) . '</p>';
        } elseif ($this->backendIniError !== null) {
            $message = mt(
                'monitoring',
                'Monitoring backend configuration could not be written to: %s; An error occured:'
            );
            $report .= '<p class="error">' . sprintf(
                $message,
                Config::resolvePath('modules/monitoring/backends.ini')
            ) . '</p><p>' . $this->backendIniError->getMessage() . '</p>';
        }

        if ($this->resourcesIniError === false) {
            $message = mt('monitoring', 'Resource configuration has been successfully updated: %s');
            $report .= '<p>' . sprintf($message, Config::resolvePath('resources.ini')) . '</p>';
        } elseif ($this->resourcesIniError !== null) {
            $message = mt('monitoring', 'Resource configuration could not be udpated: %s; An error occured:');
            $report .= '<p class="error">' . sprintf($message, Config::resolvePath('resources.ini')) . '</p>'
                . '<p>' . $this->resourcesIniError->getMessage() . '</p>';
        }

        return $report;
    }
}
