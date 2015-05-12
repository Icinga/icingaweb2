<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\Repository;

use Exception;
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
     * Insert the given data for the given target
     *
     * In case the data source provides a valid key column, $data must provide a proper
     * value for it which is then being used as the section name instead of $target.
     *
     * @param   string  $target
     * @param   array   $data
     *
     * @throws  StatementException  In case the operation has failed
     */
    public function insert($target, array $data)
    {
        $newData = $this->requireStatementColumns($target, $data);
        $section = $this->extractSectionName($target, $newData);

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
     * The section(s) to update are either identified by $filter or $target, in order. If neither of both
     * is given, all sections provided by the data source are going to be updated. Uniqueness of a section's
     * name will be ensured.
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
        if ($keyColumn && $filter === null && isset($newData[$keyColumn]) && !$this->ds->hasSection($target)) {
            throw new StatementException(
                t('Cannot update. Column "%s" holds a section\'s name which must be unique'),
                $keyColumn
            );
        }

        if ($target && !$filter) {
            if (! $this->ds->hasSection($target)) {
                throw new StatementException(t('Cannot update. Section "%s" does not exist'), $target);
            }

            $contents = array($target => $this->ds->getSection($target));
        } else {
            if ($filter) {
                $this->requireFilter($target, $filter);
            }

            $contents = iterator_to_array($this->ds);
        }

        $newSection = null;
        foreach ($contents as $section => $config) {
            if ($filter && !$filter->matches($config)) {
                continue;
            }

            if ($newSection !== null) {
                throw new StatementException(
                    t('Cannot update. Column "%s" holds a section\'s name which must be unique'),
                    $keyColumn
                );
            }

            foreach ($newData as $column => $value) {
                if ($keyColumn && $column === $keyColumn) {
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
     * The section(s) to delete are either identified by $filter or $target, in order. If neither of both
     * is given, all sections provided by the data source are going to be deleted.
     *
     * @param   string  $target
     * @param   Filter  $filter
     *
     * @throws  StatementException  In case the operation has failed
     */
    public function delete($target, Filter $filter = null)
    {
        if ($target && !$filter) {
            if (! $this->ds->hasSection($target)) {
                return; // Nothing to do
            }

            $this->ds->removeSection($target);
        } else {
            if ($filter) {
                $this->requireFilter($target, $filter);
            }

            foreach (iterator_to_array($this->ds) as $section => $config) {
                if (! $filter || $filter->matches($config)) {
                    $this->ds->removeSection($section);
                }
            }
        }

        try {
            $this->ds->saveIni();
        } catch (Exception $e) {
            throw new StatementException(t('Failed to delete. An error occurred: %s'), $e->getMessage());
        }
    }

    /**
     * Extract and return the section name off of the given $data, if available, or validate $target
     *
     * @param   string  $target
     * @param   array   $data
     *
     * @return  string
     *
     * @throws  ProgrammingError    In case no valid section name is available
     */
    protected function extractSectionName($target, array & $data)
    {
        if (($keyColumn = $this->ds->getConfigObject()->getKeyColumn())) {
            if (! isset($data[$keyColumn])) {
                throw new ProgrammingError('$data does not provide a value for key column "%s"', $keyColumn);
            }

            $target = $data[$keyColumn];
            unset($data[$keyColumn]);
        }

        if (! is_string($target)) {
            throw new ProgrammingError(
                'Neither the data source nor the $target parameter provide a valid section name'
            );
        }

        return $target;
    }
}
