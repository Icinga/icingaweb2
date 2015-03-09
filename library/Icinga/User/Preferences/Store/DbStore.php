<?php
/* Icinga Web 2 | (c) 2013-2015 Icinga Development Team | GPLv2+ */

namespace Icinga\User\Preferences\Store;

use Exception;
use Icinga\Exception\NotReadableError;
use Icinga\Exception\NotWritableError;
use Icinga\User\Preferences;
use Icinga\User\Preferences\PreferencesStore;
use Zend_Db_Expr;

/**
 * Load and save user preferences by using a database
 */
class DbStore extends PreferencesStore
{
    /**
     * Column name for username
     */
    const COLUMN_USERNAME = 'username';

    /**
     * Column name for section
     */
    const COLUMN_SECTION = 'section';

    /**
     * Column name for preference
     */
    const COLUMN_PREFERENCE = 'name';

    /**
     * Column name for value
     */
    const COLUMN_VALUE = 'value';

    /**
     * Column name for created time
     */
    const COLUMN_CREATED_TIME = 'ctime';

    /**
     * Column name for modified time
     */
    const COLUMN_MODIFIED_TIME = 'mtime';

    /**
     * Table name
     *
     * @var string
     */
    protected $table = 'icingaweb_user_preference';

    /**
     * Stored preferences
     *
     * @var array
     */
    protected $preferences = array();

    /**
     * Set the table to use
     *
     * @param   string  $table  The table name
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * Initialize the store
     */
    protected function init()
    {

    }

    /**
     * Load preferences from the database
     *
     * @return  array
     *
     * @throws  NotReadableError    In case the database operation failed
     */
    public function load()
    {
        try {
            $select = $this->getStoreConfig()->connection->getDbAdapter()->select();
            $result = $select
                ->from($this->table, array(self::COLUMN_SECTION, self::COLUMN_PREFERENCE, self::COLUMN_VALUE))
                ->where(self::COLUMN_USERNAME . ' = ?', $this->getUser()->getUsername())
                ->query()
                ->fetchAll();
        } catch (Exception $e) {
            throw new NotReadableError(
                'Cannot fetch preferences for user %s from database',
                $this->getUser()->getUsername(),
                $e
            );
        }

        if ($result !== false) {
            $values = array();
            foreach ($result as $row) {
                $values[$row->{self::COLUMN_SECTION}][$row->{self::COLUMN_PREFERENCE}] = $row->{self::COLUMN_VALUE};
            }
            $this->preferences = $values;
        }

        return $this->preferences;
    }

    /**
     * Save the given preferences in the database
     *
     * @param   Preferences     $preferences    The preferences to save
     */
    public function save(Preferences $preferences)
    {
        $preferences = $preferences->toArray();

        $sections = array_keys($preferences);

        foreach ($sections as $section) {
            if (! array_key_exists($section, $this->preferences)) {
                $this->preferences[$section] = array();
            }
            if (! array_key_exists($section, $preferences)) {
                $preferences[$section] = array();
            }
            $toBeInserted = array_diff_key($preferences[$section], $this->preferences[$section]);
            if (!empty($toBeInserted)) {
                $this->insert($toBeInserted, $section);
            }

            $toBeUpdated = array_intersect_key(
                array_diff_assoc($preferences[$section], $this->preferences[$section]),
                array_diff_assoc($this->preferences[$section], $preferences[$section])
            );
            if (!empty($toBeUpdated)) {
                $this->update($toBeUpdated, $section);
            }

            $toBeDeleted = array_keys(array_diff_key($this->preferences[$section], $preferences[$section]));
            if (!empty($toBeDeleted)) {
                $this->delete($toBeDeleted, $section);
            }
        }
    }

    /**
     * Insert the given preferences into the database
     *
     * @param   array   $preferences    The preferences to insert
     * @param   string  $section        The preferences in section to update
     *
     * @throws  NotWritableError        In case the database operation failed
     */
    protected function insert(array $preferences, $section)
    {
        /** @var \Zend_Db_Adapter_Abstract $db */
        $db = $this->getStoreConfig()->connection->getDbAdapter();

        try {
            foreach ($preferences as $key => $value) {
                $db->insert(
                    $this->table,
                    array(
                        self::COLUMN_USERNAME => $this->getUser()->getUsername(),
                        $db->quoteIdentifier(self::COLUMN_SECTION) => $section,
                        $db->quoteIdentifier(self::COLUMN_PREFERENCE) => $key,
                        self::COLUMN_VALUE => $value,
                        self::COLUMN_CREATED_TIME => new Zend_Db_Expr('NOW()'),
                        self::COLUMN_MODIFIED_TIME => new Zend_Db_Expr('NOW()')
                    )
                );
            }
        } catch (Exception $e) {
            throw new NotWritableError(
                'Cannot insert preferences for user %s into database',
                $this->getUser()->getUsername(),
                $e
            );
        }
    }

    /**
     * Update the given preferences in the database
     *
     * @param   array   $preferences    The preferences to update
     * @param   string  $section        The preferences in section to update
     *
     * @throws  NotWritableError        In case the database operation failed
     */
    protected function update(array $preferences, $section)
    {
        /** @var \Zend_Db_Adapter_Abstract $db */
        $db = $this->getStoreConfig()->connection->getDbAdapter();

        try {
            foreach ($preferences as $key => $value) {
                $db->update(
                    $this->table,
                    array(
                        self::COLUMN_VALUE => $value,
                        self::COLUMN_MODIFIED_TIME => new Zend_Db_Expr('NOW()')
                    ),
                    array(
                        self::COLUMN_USERNAME . '=?' => $this->getUser()->getUsername(),
                        $db->quoteIdentifier(self::COLUMN_SECTION) . '=?' => $section,
                        $db->quoteIdentifier(self::COLUMN_PREFERENCE) . '=?' => $key
                    )
                );
            }
        } catch (Exception $e) {
            throw new NotWritableError(
                'Cannot update preferences for user %s in database',
                $this->getUser()->getUsername(),
                $e
            );
        }
    }

    /**
     * Delete the given preference names from the database
     *
     * @param   array   $preferenceKeys     The preference names to delete
     * @param   string  $section            The preferences in section to update
     *
     * @throws  NotWritableError            In case the database operation failed
     */
    protected function delete(array $preferenceKeys, $section)
    {
        /** @var \Zend_Db_Adapter_Abstract $db */
        $db = $this->getStoreConfig()->connection->getDbAdapter();

        try {
            $db->delete(
                $this->table,
                array(
                    self::COLUMN_USERNAME . '=?' => $this->getUser()->getUsername(),
                    $db->quoteIdentifier(self::COLUMN_SECTION) . '=?' => $section,
                    $db->quoteIdentifier(self::COLUMN_PREFERENCE) . ' IN (?)' => $preferenceKeys
                )
            );
        } catch (Exception $e) {
            throw new NotWritableError(
                'Cannot delete preferences for user %s from database',
                $this->getUser()->getUsername(),
                $e
            );
        }
    }
}
