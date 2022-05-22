<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Icinga\Forms;

use Exception;
use Icinga\Common\Database;
use Icinga\Data\ConfigObject;
use Icinga\Model\ConfigScope;
use ipl\Stdlib\Filter;
use Zend_Form_Decorator_Abstract;
use Icinga\Application\Config;
use Icinga\Application\Hook\ConfigFormEventsHook;
use Icinga\Exception\ConfigurationError;
use Icinga\Web\Form;
use Icinga\Web\Notification;

/**
 * Form base-class providing standard functionality for configuration forms
 */
class ConfigForm extends Form
{
    use Database;

    /**
     * The configuration to work with
     *
     * ``ConfigObject`` if ``setConfigScope()`` is called on form
     *
     * ``Config`` if ``setIniConfig()`` is called on form
     *
     * @var Config|ConfigObject
     */
    protected $config;

    /**
     * True if resource is ini, false otherwise
     *
     * @var bool
     */
    protected $isConfigResourceIni = false;

    /**
     * @var string Scope module name in case config resource is db
     */
    protected $moduleName;

    /**
     * @var string Scope name in case config resource is db
     */
    protected $scopeName;

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
        if ($this->config !== null) {
            throw new Exception("Cannot call both methods setConfigScope() and setIniConfig() on same form");
        }

        $this->config = $config;
        $this->isConfigResourceIni = true;

        return $this;
    }

    public function getModuleName()
    {
        return $this->moduleName;
    }

    public function getScopeName()
    {
        return $this->scopeName;
    }

    /**
     * Whether the config resource is ini
     *
     * @return bool True if resource is ini, false otherwise
     */
    public function isConfigResourceIni()
    {
        return $this->isConfigResourceIni;
    }

    /**
     *
     * @param string $module Scope module name
     *
     * @param string $name Scope name
     *
     * @return $this
     */
    public function setConfigScope(string $module = 'default', string $name = 'config')
    {
        if ($this->config !== null) {
            throw new Exception("Cannot call both methods setConfigScope() and setIniConfig() on same form");
        }

        $this->scopeName = $name;
        $this->moduleName = $module;

        $this->config = self::fromDb();


        return $this;
    }

    public function createElements(array $formData)
    {
        $this->addElement(
            'hidden',
            'hash',
            [
                'value'     => $formData['hash'] ?? '',
                'required' => true
            ]
        );
    }

    public function isValid($formData)
    {
        $valid = parent::isValid($formData);

        if ($valid && ConfigFormEventsHook::runIsValid($this) === false) {
            foreach (ConfigFormEventsHook::getLastErrors() as $msg) {
                $this->error($msg);
            }

            $valid = false;
        }

        if (! $this->isConfigResourceIni()) {
            $query = ConfigScope::on($this->getDb());
            $query->filter(Filter::all(
                Filter::equal('module', $this->getModuleName()),
                Filter::equal('name', $this->getScopeName())
            ));

            try {
                $data = $this->getDb()->fetchRow($query->assembleSelect()->for('UPDATE'));
            } catch (Exception $e) {
                $this->warning($e->getMessage());

                return false;
            }

            $oldHash = $formData['hash'] ?? '';

            if ($data && $oldHash !== bin2hex($data->hash)) {
                $newOptions = self::fromDb()->toArray()['config'];
                unset($newOptions['hash']);
                $this->warning($this->translate(sprintf('The general configuration has been updated and the '
                    . 'updated general configuration is %s', json_encode($newOptions, JSON_PRETTY_PRINT))));

                $this->getElement('hash')->setValue(bin2hex($data->hash));
                return false;
            }

            if ($oldHash === '') {
                $valid = true;
            }
        }

        return $valid;
    }

    public function onSuccess()
    {
        if ($this->isConfigResourceIni()) {
            $sections = array();
            foreach (static::transformEmptyValuesToNull($this->getValues()) as $sectionAndPropertyName => $value) {
                list($section, $property) = explode('_', $sectionAndPropertyName, 2);
                $sections[$section][$property] = $value;
            }

            foreach ($sections as $section => $config) {
                $this->config->setSection($section, $config);
            }

        }

        if ($this->save()) {
            Notification::success($this->translate('New configuration has successfully been stored'));
        } else {
            return false;
        }

        if (ConfigFormEventsHook::runOnSuccess($this) === false) {
            Notification::error($this->translate(
                'Configuration successfully stored. Though, one or more module hooks failed to run.'
                . ' See logs for details'
            ));
        }
    }

    public function onRequest()
    {
        $values = [];
        foreach ($this->config as $section => $properties) {
            foreach ($properties as $name => $value) {
                $sectionPrefix = '';
                if ($this->isConfigResourceIni()) {
                    $sectionPrefix = $section . '_';
                }

                $values[$sectionPrefix . $name] = $value;
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
        } catch (ConfigurationError $e) {
            $this->addError($e->getMessage());

            return false;
        } catch (Exception $e) {
            if (! $this->isConfigResourceIni()) {
                $this->addError($e->getMessage());

                return false;
            }

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
     * @param Config|ConfigObject $config
     *
     * @throws ConfigurationError
     */
    protected function writeConfig($config)
    {
        if ($this->isConfigResourceIni()) {
            $config->saveIni();

            return;
        }

        $values = static::transformEmptyValuesToNull($this->getValues());
        unset($values['hash']);

        $valuesAsStr = implode(', ', array_map(
            function ($v, $k) { return sprintf("%s=%s", $k, $v); },
            $values,
            array_keys($values)
        ));

        $newHash = sha1($valuesAsStr, true);

        $db = $this->getDb();

        $data = ConfigScope::on($db)->with(['option']);
        $data->filter(Filter::all(
            Filter::equal('module', $this->getModuleName()),
            Filter::equal('name', $this->getScopeName())
        ));

        $data = $data->first();

        $db->beginTransaction();
        try {
            if ($data === null) {
                $db->insert('icingaweb_config_scope', [
                    'module'    => $this->getModuleName(),
                    'name'      => $this->getScopeName(),
                    'hash'      => $newHash
                ]);
                $id = $db->lastInsertId();

                foreach ($values as $k => $v) {
                    if (isset($v)) {
                        $db->insert('icingaweb_config_option', [
                            'scope_id'      => $id,
                            'name'    => $k,
                            'value'   => $v
                        ]);
                    }
                }

            } elseif ($data->hash !== $newHash) {
                $db->update('icingaweb_config_scope', [
                    'module'    => $this->getModuleName(),
                    'name'      => $this->getScopeName(),
                    'hash'      => $newHash
                ], ['id = ?' => $data->id]);

                $db->delete('icingaweb_config_option', ['scope_id = ?' => $data->id]);

                foreach ($values as $k => $v) {
                    $db->insert('icingaweb_config_option', [
                        'scope_id'=>  $data->id,
                        'name'    => $k,
                        'value'   => $v
                    ]);
                }
            }

            $db->commitTransaction();
        } catch (Exception $e) {
            $db->rollBackTransaction();
            throw $e;
        }
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

    protected function fromDb()
    {
        $data = ConfigScope::on($this->getDb())->with(['option']);
        $data->filter(Filter::all(
            Filter::equal('module', $this->getModuleName()),
            Filter::equal('name', $this->getScopeName())
        ));

        $options = [];

        foreach ($data as $values) {
            $options[$values->name][$values->option->name] = $values->option->value;
            $options[$values->name]['hash'] = bin2hex($values->hash);
        }

        return new ConfigObject($options);
    }
}
