<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Module\Setup\Steps;

use Exception;
use Icinga\Application\Logger;
use Icinga\Application\Config;
use Icinga\Exception\IcingaException;
use Icinga\Module\Setup\Step;

class GeneralConfigStep extends Step
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
        foreach ($this->data['generalConfig'] as $sectionAndPropertyName => $value) {
            list($section, $property) = explode('_', $sectionAndPropertyName, 2);
            $config[$section][$property] = $value;
        }

        if ($config['global']['config_backend'] === 'db') {
            $config['global']['config_resource'] = $this->data['resourceName'];
        }

        try {
            Config::fromArray($config)
                ->setConfigFile(Config::resolvePath('config.ini'))
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
        $pageTitle = '<h2>' . mt('setup', 'Application Configuration', 'setup.page.title') . '</h2>';
        $generalTitle = '<h3>' . t('General', 'app.config') . '</h3>';
        $loggingTitle = '<h3>' . t('Logging', 'app.config') . '</h3>';

        $generalHtml = ''
            . '<ul>'
            . '<li>' . ($this->data['generalConfig']['global_show_stacktraces']
                ? t('An exception\'s stacktrace is shown to every user by default.')
                : t('An exception\'s stacktrace is hidden from every user by default.')
            ) . '</li>'
            . '<li>' . sprintf(
                $this->data['generalConfig']['global_config_backend'] === 'ini' ? sprintf(
                    t('Preferences will be stored per user account in INI files at: %s'),
                    Config::resolvePath('preferences')
                ) : t('Preferences will be stored using a database.')
            ) . '</li>'
            . '</ul>';

        $type = $this->data['generalConfig']['logging_log'];
        if ($type === 'none') {
            $loggingHtml = '<p>' . mt('setup', 'Logging will be disabled.') . '</p>';
        } else {
            $level = $this->data['generalConfig']['logging_level'];
            $loggingHtml = ''
                . '<table>'
                . '<tbody>'
                . '<tr>'
                . '<td><strong>' . t('Type', 'app.config.logging') . '</strong></td>'
                . '<td>' . ($type === 'syslog' ? 'Syslog' : t('File', 'app.config.logging.type')) . '</td>'
                . '</tr>'
                . '<tr>'
                . '<td><strong>' . t('Level', 'app.config.logging') . '</strong></td>'
                . '<td>' . ($level === Logger::$levels[Logger::ERROR] ? t('Error', 'app.config.logging.level') : (
                    $level === Logger::$levels[Logger::WARNING] ? t('Warning', 'app.config.logging.level') : (
                        $level === Logger::$levels[Logger::INFO] ? t('Information', 'app.config.logging.level') : (
                            t('Debug', 'app.config.logging.level')
                        )
                    )
                )) . '</td>'
                . '</tr>'
                . '<tr>'
                . ($type === 'syslog' ? (
                    '<td><strong>' . t('Application Prefix') . '</strong></td>'
                    . '<td>' . $this->data['generalConfig']['logging_application'] . '</td>'
                ) : (
                    '<td><strong>' . t('Filepath') . '</strong></td>'
                    . '<td>' . $this->data['generalConfig']['logging_file'] . '</td>'
                ))
                . '</tr>'
                . '</tbody>'
                . '</table>';
        }

        return $pageTitle . '<div class="topic">' . $generalTitle . $generalHtml . '</div>'
            . '<div class="topic">' . $loggingTitle . $loggingHtml . '</div>';
    }

    public function getReport()
    {
        if ($this->error === false) {
            return array(sprintf(
                mt('setup', 'General configuration has been successfully written to: %s'),
                Config::resolvePath('config.ini')
            ));
        } elseif ($this->error !== null) {
            return array(
                sprintf(
                    mt('setup', 'General configuration could not be written to: %s. An error occured:'),
                    Config::resolvePath('config.ini')
                ),
                sprintf(mt('setup', 'ERROR: %s'), IcingaException::describe($this->error))
            );
        }
    }
}
