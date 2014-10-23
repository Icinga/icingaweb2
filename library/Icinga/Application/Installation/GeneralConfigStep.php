<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application\Installation;

use Exception;
use Zend_Config;
use Icinga\Web\Setup\Step;
use Icinga\Application\Config;
use Icinga\Config\PreservingIniWriter;

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
            $writer = new PreservingIniWriter(array(
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
        return '';
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

        return '';
    }
}
