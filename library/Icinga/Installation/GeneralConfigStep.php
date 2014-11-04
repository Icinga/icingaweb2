<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Installation;

use Exception;
use Zend_Config;
use Icinga\Logger\Logger;
use Icinga\Web\Setup\Step;
use Icinga\Application\Config;
use Icinga\File\Ini\IniWriter;

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
            list($section, $property) = explode('_', $sectionAndPropertyName);
            $config[$section][$property] = $value;
        }

        $config['preferences']['type'] = $this->data['preferencesType'];
        if (isset($this->data['preferencesResource'])) {
            $config['preferences']['resource'] = $this->data['preferencesResource'];
        }

        try {
            $writer = new IniWriter(array(
                'config'    => new Zend_Config($config),
                'filename'  => Config::resolvePath('config.ini'),
                'filemode'  => octdec($this->data['fileMode'])
            ));
            $writer->write();
        } catch (Exception $e) {
            $this->error = $e;
            return false;
        }

        $this->error = false;
        return true;
    }

    public function getSummary()
    {
        $pageTitle = '<h2>' . t('Application Configuration') . '</h2>';
        $generalTitle = '<h3>' . t('General', 'app.config') . '</h3>';
        $loggingTitle = '<h3>' . t('Logging', 'app.config') . '</h3>';

        $generalHtml = ''
            . '<ul>'
            . '<li>' . sprintf(
                t('Icinga Web 2 will save new configuration files using the mode "%s".'),
                $this->data['generalConfig']['global_filemode']
            ) . '</li>'
            . '<li>' . sprintf(
                $this->data['preferencesType'] === 'ini' ? sprintf(
                    t('Preferences will be stored per user account in INI files at: %s'),
                    Config::resolvePath('preferences')
                ) : (
                    $this->data['preferencesType'] === 'db' ? t('Preferences will be stored using a database.') : (
                        t('Preferences will not be persisted across browser sessions.')
                    )
                )
            ) . '</li>'
            . '</ul>';

        $type = $this->data['generalConfig']['logging_log'];
        if ($type === 'none') {
            $loggingHtml = '<p>' . t('Logging will be disabled.') . '</p>';
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
            $message = t('General configuration has been successfully written to: %s');
            return '<p>' . sprintf($message, Config::resolvePath('config.ini')) . '</p>';
        } elseif ($this->error !== null) {
            $message = t('General configuration could not be written to: %s; An error occured:');
            return '<p class="error">' . sprintf($message, Config::resolvePath('config.ini')) . '</p>'
                . '<p>' . $this->error->getMessage() . '</p>';
        }
    }
}
