<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use Exception;
use Zend_Form_Decorator_Abstract;
use Icinga\Application\Config;
use Icinga\Web\Form;
use Icinga\Web\Notification;

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
     * {@inheritdoc}
     *
     * Values from subforms are directly added to the returned values array instead of being grouped by the subforms'
     * names.
     */
    public function getValues($suppressArrayNotation = false)
    {
        $values = parent::getValues($suppressArrayNotation);
        foreach (array_keys($this->_subForms) as $name) {
            // Zend returns values from subforms grouped by their names, but we want them flat
            $values = array_merge($values, $values[$name]);
            unset($values[$name]);
        }
        return $values;
    }

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
     * {@inheritdoc}
     */
    public function onSuccess()
    {
        $sections = array();
        foreach (static::transformEmptyValuesToNull($this->getValues()) as $sectionAndPropertyName => $value) {
            list($section, $property) = explode('_', $sectionAndPropertyName, 2);
            $sections[$section][$property] = $value;
        }

        foreach ($sections as $section => $config) {
            $this->config->setSection($section, $config);
        }

        if ($this->save()) {
            Notification::success($this->translate('New configuration has successfully been stored'));
        } else {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onRequest()
    {
        $values = array();
        foreach ($this->config as $section => $properties) {
            foreach ($properties as $name => $value) {
                $values[$section . '_' . $name] = $value;
            }
        }

        $this->populate($values);
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
            $this->writeConfig($this->config);
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

    /**
     * Write the configuration to disk
     *
     * @param   Config  $config
     */
    protected function writeConfig(Config $config)
    {
        $config->saveIni();
    }

    /**
     * Transform all empty values of the given array to null
     *
     * @param   array   $values
     *
     * @return  array
     */
    public static function transformEmptyValuesToNull(array $values)
    {
        array_walk($values, function (&$v) {
            if ($v === '' || $v === false || $v === array()) {
                $v = null;
            }
        });

        return $values;
    }
}
