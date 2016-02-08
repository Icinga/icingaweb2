<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Monitoring;

use Exception;
use Icinga\Module\Setup\Step;
use Icinga\Application\Config;
use Icinga\Exception\IcingaException;

class SecurityStep extends Step
{
    protected $data;

    protected $error;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function apply()
    {
        $config = array();
        $config['security'] = $this->data['securityConfig'];

        try {
            Config::fromArray($config)
                ->setConfigFile(Config::resolvePath('modules/monitoring/config.ini'))
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
        $pageTitle = '<h2>' . mt('monitoring', 'Monitoring Security', 'setup.page.title') . '</h2>';
        $pageDescription = '<p>' . mt(
            'monitoring',
            'Icinga Web 2 will protect your monitoring environment against'
            . ' prying eyes using the configuration specified below:'
        ) . '</p>';

        $pageHtml = ''
            . '<table>'
            . '<tbody>'
            . '<tr>'
            . '<td><strong>' . mt('monitoring', 'Protected Custom Variables') . '</strong></td>'
            . '<td>' . ($this->data['securityConfig']['protected_customvars'] ? (
                $this->data['securityConfig']['protected_customvars']
            ) : mt('monitoring', 'None', 'monitoring.protected_customvars')) . '</td>'
            . '</tr>'
            . '</tbody>'
            . '</table>';

        return $pageTitle . '<div class="topic">' . $pageDescription . $pageHtml . '</div>';
    }

    public function getReport()
    {
        if ($this->error === false) {
            return array(sprintf(
                mt('monitoring', 'Monitoring security configuration has been successfully created: %s'),
                Config::resolvePath('modules/monitoring/config.ini')
            ));
        } elseif ($this->error !== null) {
            return array(
                sprintf(
                    mt(
                        'monitoring',
                        'Monitoring security configuration could not be written to: %s. An error occured:'
                    ),
                    Config::resolvePath('modules/monitoring/config.ini')
                ),
                sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->error))
            );
        }
    }
}
