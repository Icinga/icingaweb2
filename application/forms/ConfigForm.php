<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use Exception;
use Zend_Form_Decorator_Abstract;
use Icinga\Web\Form;
use Icinga\Application\Config;

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
     * @return  $this
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
        try {
            $this->config->saveIni();
        } catch (Exception $e) {
            $this->addDecorator('ViewScript', array(
                'viewModule'    => 'default',
                'viewScript'    => 'showConfiguration.phtml',
                'errorMessage'  => $e->getMessage(),
                'configString'  => $this->config,
                'filePath'      => $this->config->getConfigFile(),
                'placement'     => Zend_Form_Decorator_Abstract::PREPEND
            ));
            return false;
        }

        return true;
    }
}
