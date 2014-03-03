<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga Web 2.
 *
 * Icinga Web 2 - Head for multiple monitoring backends.
 * Copyright (C) 2013 Icinga Development Team
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @copyright  2013 Icinga Development Team <info@icinga.org>
 * @license    http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author     Icinga Development Team <info@icinga.org>
 *
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\User\Preferences;

use Icinga\User;
use SplSubject;
use Icinga\Exception\ProgrammingError;
use Icinga\User\Preferences;
use Icinga\Data\ResourceFactory;

/**
 * Store user preferences in database
 */
class DbStore implements LoadInterface, FlushObserverInterface
{
    /**
     * Column name for username
     */
    const COLUMN_USERNAME = 'username';

    /**
     * Column name for preference
     */
    const COLUMN_PREFERENCE = 'key';

    /**
     * Column name for value
     */
    const COLUMN_VALUE = 'value';

    /**
     * User object
     *
     * @var User
     */
    private $user;

    /**
     * Zend database adapter
     *
     * @var Zend_Db_Adapter_Abstract
     */
    private $db;

    /**
     * Table name
     *
     * @var string
     */
    private $table = 'preference';

    /**
     * Setter for user
     *
     * @param User $user
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        ResourceFactory::createResource(
            ResourceFactory::getResourceConfig($config->resource)
        );
    }

    /**
     * Setter for db adapter
     *
     * @param Zend_Db_Adapter_Abstract $db
     */
    public function setDbAdapter( $db)
    {
        $this->db = $db;

    }

    /**
     * Setter for table
     *
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * Load preferences from source
     *
     * @return array
     */
    public function load()
    {
        $res = $this->db->select()->from($this->table)
            ->where('username=?', $this->user->getUsername());

        $out = array();

        foreach ($res->fetchAll() as $row) {
            $out[$row->{self::COLUMN_PREFERENCE}] = $row->{self::COLUMN_VALUE};
        }

        return $out;
    }

    /**
     * Helper to create zend db suitable where condition
     *
     * @param  string $preference
     * @return array
     */
    private function createWhereCondition($preference)
    {
        return array(
            $this->db->quoteIdentifier(self::COLUMN_USERNAME) .   '=?' => $this->user->getUsername(),
            $this->db->quoteIdentifier(self::COLUMN_PREFERENCE) . '=?' => $preference
        );
    }

    /**
     * Create operation
     *
     * @param  string $preference
     * @param  mixed  $value
     * @return int
     */
    private function doCreate($preference, $value)
    {
        return $this->db->insert(
            $this->table,
            array(
                $this->db->quoteIdentifier(self::COLUMN_USERNAME)   => $this->user->getUsername(),
                $this->db->quoteIdentifier(self::COLUMN_PREFERENCE) => $preference,
                $this->db->quoteIdentifier(self::COLUMN_VALUE)      => $value
            )
        );
    }

    /**
     * Update operation
     *
     * @param  string $preference
     * @param  mixed  $value
     * @return int
     */
    private function doUpdate($preference, $value)
    {
        return $this->db->update(
            $this->table,
            array(
                self::COLUMN_VALUE => $value
            ),
            $this->createWhereCondition($preference)
        );
    }

    /**
     * Delete preference operation
     *
     * @param  string $preference
     * @return int
     */
    private function doDelete($preference)
    {
        return $this->db->delete(
            $this->table,
            $this->createWhereCondition($preference)
        );
    }

    /**
     * Receive update from subject
     *
     * @link http://php.net/manual/en/splobserver.update.php
     * @param SplSubject $subject
     * @throws ProgrammingError
     */
    public function update(SplSubject $subject)
    {
        if (!$subject instanceof Preferences) {
            throw new ProgrammingError('Not compatible with '. get_class($subject));
        }

        $changeSet = $subject->getChangeSet();

        foreach ($changeSet->getCreate() as $key => $value) {
            $retVal = $this->doCreate($key, $value);

            if (!$retVal) {
                throw new ProgrammingError('Could not create preference value in db: '. $key. '='. $value);
            }
        }

        foreach ($changeSet->getUpdate() as $key => $value) {
            $retVal = $this->doUpdate($key, $value);

            /*
             * Fallback if we switch storage type while user logged in
             */
            if (!$retVal) {
                $retVal = $this->doCreate($key, $value);

                if (!$retVal) {
                    throw new ProgrammingError('Could not create preference value in db: '. $key. '='. $value);
                }
            }
        }

        foreach ($changeSet->getDelete() as $key) {
            $retVal = $this->doDelete($key);

            if (!$retVal) {
                throw new ProgrammingError('Could not delete preference value in db: '. $key);
            }
        }
    }
}
