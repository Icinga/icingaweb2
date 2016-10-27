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
 * </ul>
 */
abstract class IniRepository extends Repository implements Extensible, Updatable, Reducible
{
    /**
     * The datasource being used
     *
     * @var Config
     */
    protected $ds;

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
     * @param   Config  $ds         The data source to use
     *
     * @throws  ProgrammingError    In case the given data source does not provide a valid key column
     */
    public function __construct(Config $ds)
    {
        parent::__construct($ds); // First! Due to init().

        if (! $ds->getConfigObject()->getKeyColumn()) {
            throw new ProgrammingError('INI repositories require their data source to provide a valid key column');
        }
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
     *
     * @throws  ProgrammingError    In case the table is registered as having triggers but not any trigger is found
     */
    protected function getTrigger($table, $event)
    {
        if (! in_array($table, $this->getTriggers())) {
            return;
        }

        $identifier = join('', array_map('ucfirst', explode('_', $table)));
        if (! method_exists($this, $event . $identifier)) {
            throw new ProgrammingError(
                'Cannot find any trigger for table "%s". Add a trigger or remove the table from %s::$triggers',
                $table,
                get_class($this)
            );
        }

        return $event . $identifier;
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
        $newData = $this->requireStatementColumns($target, $data);
        $section = $this->extractSectionName($newData);

        if ($this->ds->hasSection($section)) {
            throw new StatementException(t('Cannot insert. Section "%s" does already exist'), $section);
        }

        $this->ds->setSection($section, $this->onInsert($target, new ConfigObject($newData)));

        try {
            $this->ds->saveIni();
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
        $newData = $this->requireStatementColumns($target, $data);
        $keyColumn = $this->ds->getConfigObject()->getKeyColumn();
        if ($filter === null && isset($newData[$keyColumn])) {
            throw new StatementException(
                t('Cannot update. Column "%s" holds a section\'s name which must be unique'),
                $keyColumn
            );
        }

        $query = $this->ds->select();
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
                if ($this->ds->hasSection($newSection)) {
                    throw new StatementException(t('Cannot update. Section "%s" does already exist'), $newSection);
                }

                $this->ds->removeSection($section)->setSection(
                    $newSection,
                    $this->onUpdate($target, $config, $newConfig)
                );
            } else {
                $this->ds->setSection(
                    $section,
                    $this->onUpdate($target, $config, $newConfig)
                );
            }
        }

        try {
            $this->ds->saveIni();
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
        $query = $this->ds->select();
        if ($filter !== null) {
            $query->addFilter($this->requireFilter($target, $filter));
        }

        /** @var ConfigObject $config */
        foreach ($query as $section => $config) {
            $this->ds->removeSection($section);
            $this->onDelete($target, $config);
        }

        try {
            $this->ds->saveIni();
        } catch (Exception $e) {
            throw new StatementException(t('Failed to delete. An error occurred: %s'), $e->getMessage());
        }
    }

    /**
     * Extract and return the section name off of the given $data
     *
     * @param   array   $data
     *
     * @return  string
     *
     * @throws  ProgrammingError    In case no valid section name is available
     */
    protected function extractSectionName(array & $data)
    {
        $keyColumn = $this->ds->getConfigObject()->getKeyColumn();
        if (! isset($data[$keyColumn])) {
            throw new ProgrammingError('$data does not provide a value for key column "%s"', $keyColumn);
        }

        $section = $data[$keyColumn];
        unset($data[$keyColumn]);
        return $section;
    }
}
