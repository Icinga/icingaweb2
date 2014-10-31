<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\Form;

use Exception;
use Icinga\Web\Form;
use Icinga\Application\Config;
use Icinga\File\Ini\IniWriter;

/**
 * Form base-class providing standard functionality for configuration forms
 */
class ConfigForm extends Form
{
    /**
     * The configuration to work with
     *
     * @var Config
     */
    protected $config;

    /**
     * Set the configuration to use when populating the form or when saving the user's input
     *
     * @param   Config      $config     The configuration to use
     *
     * @return  self
     */
    public function setIniConfig(Config $config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Persist the current configuration to disk
     *
     * If an error occurs the user is shown a view describing the issue and displaying the raw INI configuration.
     *
     * @return  bool                    Whether the configuration could be persisted
     */
    public function save()
    {
        $writer = new IniWriter(
            array(
                'config'    => $this->config,
                'filename'  => $this->config->getConfigFile()
            )
        );

        try {
            $writer->write();
        } catch (Exception $e) {
            $this->addDecorator('ViewScript', array(
                'viewModule'    => 'default',
                'viewScript'    => 'showConfiguration.phtml',
                'errorMessage'  => $e->getMessage(),
                'configString'  => $writer->render(),
                'filePath'      => $this->config->getConfigFile()
            ));
            return false;
        }

        return true;
    }
}
