<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Repository;

use Exception;
use Icinga\Application\Config;
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

        $this->ds->setSection($section, $newData);

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

        if ($filter !== null) {
            $filter = $this->requireFilter($target, $filter);
        }

        $newSection = null;
        foreach (iterator_to_array($this->ds) as $section => $config) {
            if ($filter !== null && !$filter->matches($config)) {
                continue;
            }

            if ($newSection !== null) {
                throw new StatementException(
                    t('Cannot update. Column "%s" holds a section\'s name which must be unique'),
                    $keyColumn
                );
            }

            foreach ($newData as $column => $value) {
                if ($column === $keyColumn) {
                    $newSection = $value;
                } else {
                    $config->$column = $value;
                }
            }

            if ($newSection) {
                if ($this->ds->hasSection($newSection)) {
                    throw new StatementException(t('Cannot update. Section "%s" does already exist'), $newSection);
                }

                $this->ds->removeSection($section)->setSection($newSection, $config);
            } else {
                $this->ds->setSection($section, $config);
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
        if ($filter !== null) {
            $filter = $this->requireFilter($target, $filter);
        }

        foreach (iterator_to_array($this->ds) as $section => $config) {
            if ($filter === null || $filter->matches($config)) {
                $this->ds->removeSection($section);
            }
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
