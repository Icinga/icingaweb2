<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Application\Installation;

use Exception;
use Zend_Config;
use Icinga\Web\Setup\Step;
use Icinga\Application\Config;
use Icinga\Config\PreservingIniWriter;

class ResourceStep extends Step
{
    protected $data;

    protected $error;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function apply()
    {
        $resourceConfig = array();
        if (isset($this->data['dbResourceConfig'])) {
            $dbConfig = $this->data['dbResourceConfig'];
            $resourceName = $dbConfig['name'];
            unset($dbConfig['name']);
            $resourceConfig[$resourceName] = $dbConfig;
        }

        if (isset($this->data['ldapResourceConfig'])) {
            $ldapConfig = $this->data['ldapResourceConfig'];
            $resourceName = $ldapConfig['name'];
            unset($ldapConfig['name']);
            $resourceConfig[$resourceName] = $ldapConfig;
        }

        try {
            $writer = new PreservingIniWriter(array(
                'config'    => new Zend_Config($resourceConfig),
                'filename'  => Config::resolvePath('resources.ini'),
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
            $message = t('Resource configuration has been successfully written to: %s');
            return '<p>' . sprintf($message, Config::resolvePath('resources.ini')) . '</p>';
        } elseif ($this->error !== null) {
            $message = t('Resource configuration could not be written to: %s; An error occured:');
            return '<p class="error">' . sprintf($message, Config::resolvePath('resources.ini')) . '</p>'
                . '<p>' . $this->error->getMessage() . '</p>';
        }

        return '';
    }
}
