<?php
/* Icinga Web 2 | (c) 2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Repository;

use Exception;
use Icinga\Application\Config;
use Icinga\Data\ConfigObject;
use Icinga\Data\Extensible;
use Icinga\Data\Filter\Filter;
use Icinga\Data\Updatable;
use Icinga\Data\Reducible;
use Icinga\Exception\ProgrammingError;
use Icinga\Exception\StatementException;

/**
 * Abstract base class for concrete INI repository implementations
 *
 * Additionally provided features:
 * <ul>
 *  <li>Insert, update and delete capabilities</li>
 *  <li>Triggers for inserts, updates and deletions</li>
 *  <li>Lazy initialization of table specific configs</li>
 * </ul>
 */
abstract class IniRepository extends Repository implements Extensible, Updatable, Reducible
{
    /**
     * The configuration files used as table specific datasources
     *
     * This must be initialized by concrete repository implementations, in the following format
     * <code>
     * array(
     *   'table_name' => array(
     *     'config'    => 'name_of_the_ini_file_without_extension',
     *     'keyColumn' => 'the_name_of_the_column_to_use_as_key_column',
     *    ['module'    => 'the_name_of_the_module_if_any']
     *   )
     * )
     * </code>
     *
     * @var array
     */
    protected $configs;

    /**
     * The tables for which triggers are available when inserting, updating or deleting rows
     *
     * This may be initialized by concrete repository implementations and describes for which table names triggers
     * are available. The repository attempts to find a method depending on the type of event and table for which
     * to run the trigger. The name of such a method is expected to be declared using lowerCamelCase.
     * (e.g. group_membership will be translated to onUpdateGroupMembership and groupmembership will be translated
     * to onUpdateGroupmembership) The available events are onInsert, onUpdate and onDelete.
     *
     * @var array
     */
    protected $triggers;

    /**
     * Create a new INI repository object
     *
     * @param   Config|null $ds     The data source to use
     *
     * @throws  ProgrammingError    In case the given data source does not provide a valid key column
     */
    public function __construct(Config $ds = null)
    {
        parent::__construct($ds); // First! Due to init().

        if ($ds !== null && !$ds->getConfigObject()->getKeyColumn()) {
            throw new ProgrammingError('INI repositories require their data source to provide a valid key column');
        }
    }

    /**
     * {@inheritDoc}
     *
     * @return  Config
     */
    public function getDataSource($table = null)
    {
        if ($this->ds !== null) {
            return parent::getDataSource($table);
        }

        $table = $table ?: $this->getBaseTable();
        $configs = $this->getConfigs();
        if (! isset($configs[$table])) {
            throw new ProgrammingError('Config for table "%s" missing', $table);
        } elseif (! $configs[$table] instanceof Config) {
            $configs[$table] = $this->createConfig($configs[$table], $table);
        }

        if (! $configs[$table]->getConfigObject()->getKeyColumn()) {
            throw new ProgrammingError(
                'INI repositories require their data source to provide a valid key column'
            );
        }

        return $configs[$table];
    }

    /**
     * Return the configuration files used as table specific datasources
     *
     * Calls $this->initializeConfigs() in case $this->configs is null.
     *
     * @return  array
     */
    public function getConfigs()
    {
        if ($this->configs === null) {
            $this->configs = $this->initializeConfigs();
        }

        return $this->configs;
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize the configs lazily
     *
     * @return  array
     */
    protected function initializeConfigs()
    {
        return array();
    }

    /**
     * Return the tables for which triggers are available when inserting, updating or deleting rows
     *
     * Calls $this->initializeTriggers() in case $this->triggers is null.
     *
     * @return  array
     */
    public function getTriggers()
    {
        if ($this->triggers === null) {
            $this->triggers = $this->initializeTriggers();
        }

        return $this->triggers;
    }

    /**
     * Overwrite this in your repository implementation in case you need to initialize the triggers lazily
     *
     * @return  array
     */
    protected function initializeTriggers()
    {
        return array();
    }

    /**
     * Run a trigger for the given table and row which is about to be inserted
     *
     * @param   string          $table
     * @param   ConfigObject    $new
     *
     * @return  ConfigObject
     */
    public function onInsert($table, ConfigObject $new)
    {
        $trigger = $this->getTrigger($table, 'onInsert');
        if ($trigger !== null) {
            $row = $this->$trigger($new);
            if ($row !== null) {
                $new = $row;
            }
        }

        return $new;
    }

    /**
     * Run a trigger for the given table and row which is about to be updated
     *
     * @param   string          $table
     * @param   ConfigObject    $old
     * @param   ConfigObject    $new
     *
     * @return  ConfigObject
     */
    public function onUpdate($table, ConfigObject $old, ConfigObject $new)
    {
        $trigger = $this->getTrigger($table, 'onUpdate');
        if ($trigger !== null) {
            $row = $this->$trigger($old, $new);
            if ($row !== null) {
                $new = $row;
            }
        }

        return $new;
    }

    /**
     * Run a trigger for the given table and row which has been deleted
     *
     * @param   string          $table
     * @param   ConfigObject    $old
     *
     * @return  ConfigObject
     */
    public function onDelete($table, ConfigObject $old)
    {
        $trigger = $this->getTrigger($table, 'onDelete');
        if ($trigger !== null) {
            $this->$trigger($old);
        }
    }

    /**
     * Return the name of the trigger method for the given table and event-type
     *
     * @param   string  $table  The table name for which to return a trigger method
     * @param   string  $event  The name of the event type
     *
     * @return  string
     */
    protected function getTrigger($table, $event)
    {
        if (! in_array($table, $this->getTriggers())) {
            return;
        }

        $identifier = join('', array_map('ucfirst', explode('_', $table)));
        if (method_exists($this, $event . $identifier)) {
            return $event . $identifier;
        }
    }

    /**
     * Insert the given data for the given target
     *
     * $data must provide a proper value for the data source's key column.
     *
     * @param   string  $target
     * @param   array   $data
     *
     * @throws  StatementException  In case the operation has failed
     */
    public function insert($target, array $data)
    {
        $ds = $this->getDataSource($target);
        $newData = $this->requireStatementColumns($target, $data);

        $config = $this->onInsert($target, new ConfigObject($newData));
        $section = $this->extractSectionName($config, $ds->getConfigObject()->getKeyColumn());

        if ($ds->hasSection($section)) {
            throw new StatementException(t('Cannot insert. Section "%s" does already exist'), $section);
        }

        $ds->setSection($section, $config);

        try {
            $ds->saveIni();
        } catch (Exception $e) {
            throw new StatementException(t('Failed to insert. An error occurred: %s'), $e->getMessage());
        }
    }

    /**
     * Update the target with the given data and optionally limit the affected entries by using a filter
     *
     * @param   string  $target
     * @param   array   $data
     * @param   Filter  $filter
     *
     * @throws  StatementException  In case the operation has failed
     */
    public function update($target, array $data, Filter $filter = null)
    {
        $ds = $this->getDataSource($target);
        $newData = $this->requireStatementColumns($target, $data);

        $keyColumn = $ds->getConfigObject()->getKeyColumn();
        if ($filter === null && isset($newData[$keyColumn])) {
            throw new StatementException(
                t('Cannot update. Column "%s" holds a section\'s name which must be unique'),
                $keyColumn
            );
        }

        $query = $ds->select();
        if ($filter !== null) {
            $query->addFilter($this->requireFilter($target, $filter));
        }

        /** @var ConfigObject $config */
        $newSection = null;
        foreach ($query as $section => $config) {
            if ($newSection !== null) {
                throw new StatementException(
                    t('Cannot update. Column "%s" holds a section\'s name which must be unique'),
                    $keyColumn
                );
            }

            $newConfig = clone $config;
            foreach ($newData as $column => $value) {
                if ($column === $keyColumn) {
                    $newSection = $value;
                } else {
                    $newConfig->$column = $value;
                }
            }

            // This is necessary as the query result set contains the key column.
            unset($newConfig->$keyColumn);

            if ($newSection) {
                if ($ds->hasSection($newSection)) {
                    throw new StatementException(t('Cannot update. Section "%s" does already exist'), $newSection);
                }

                $ds->removeSection($section)->setSection(
                    $newSection,
                    $this->onUpdate($target, $config, $newConfig)
                );
            } else {
                $ds->setSection(
                    $section,
                    $this->onUpdate($target, $config, $newConfig)
                );
            }
        }

        try {
            $ds->saveIni();
        } catch (Exception $e) {
            throw new StatementException(t('Failed to update. An error occurred: %s'), $e->getMessage());
        }
    }

    /**
     * Delete entries in the given target, optionally limiting the affected entries by using a filter
     *
     * @param   string  $target
     * @param   Filter  $filter
     *
     * @throws  StatementException  In case the operation has failed
     */
    public function delete($target, Filter $filter = null)
    {
        $ds = $this->getDataSource($target);

        $query = $ds->select();
        if ($filter !== null) {
            $query->addFilter($this->requireFilter($target, $filter));
        }

        /** @var ConfigObject $config */
        foreach ($query as $section => $config) {
            $ds->removeSection($section);
            $this->onDelete($target, $config);
        }

        try {
            $ds->saveIni();
        } catch (Exception $e) {
            throw new StatementException(t('Failed to delete. An error occurred: %s'), $e->getMessage());
        }
    }

    /**
     * Create and return a Config for the given meta and table
     *
     * @param   array   $meta
     * @param   string  $table
     *
     * @return  Config
     *
     * @throws  ProgrammingError    In case the given meta is invalid
     */
    protected function createConfig(array $meta, $table)
    {
        if (! isset($meta['name'])) {
            throw new ProgrammingError('Config file name missing for table "%s"', $table);
        } elseif (! isset($meta['keyColumn'])) {
            throw new ProgrammingError('Config key column name missing for table "%s"', $table);
        }

        if (isset($meta['module'])) {
            $config = Config::module($meta['module'], $meta['name']);
        } else {
            $config = Config::app($meta['name']);
        }

        $config->getConfigObject()->setKeyColumn($meta['keyColumn']);
        return $config;
    }

    /**
     * Extract and return the section name off of the given $config
     *
     * @param   array|ConfigObject  $config
     * @param   string              $keyColumn
     *
     * @return  string
     *
     * @throws  ProgrammingError    In case no valid section name is available
     */
    protected function extractSectionName(& $config, $keyColumn)
    {
        if (! is_array($config) && !$config instanceof ConfigObject) {
            throw new ProgrammingError('$config is neither an array nor a ConfigObject');
        } elseif (! isset($config[$keyColumn])) {
            throw new ProgrammingError('$config does not provide a value for key column "%s"', $keyColumn);
        }

        $section = $config[$keyColumn];
        unset($config[$keyColumn]);
        return $section;
    }
}
