<?php
// {{{ICINGA_LICENSE_HEADER}}}
/**
 * This file is part of Icinga 2 Web.
 *
 * Icinga 2 Web - Head for multiple monitoring backends.
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
 * @copyright 2013 Icinga Development Team <info@icinga.org>
 * @license   http://www.gnu.org/licenses/gpl-2.0.txt GPL, version 2
 * @author    Icinga Development Team <info@icinga.org>
 */
// {{{ICINGA_LICENSE_HEADER}}}

namespace Icinga\User;

use \SplObjectStorage;
use \SplObserver;
use \SplSubject;
use Icinga\User\Preferences\ChangeSet;
use Icinga\Exception\ProgrammingError;

/**
 * Handling retrieve and persist of user preferences
 */
class Preferences implements SplSubject, \Countable
{
    /**
     * Container for all preferences
     *
     * @var array
     */
    private $preferences = array();

    /**
     * All observers for changes
     *
     * @var SplObserver[]
     */
    private $observer = array();

    /**
     * Current change set
     *
     * @var ChangeSet
     */
    private $changeSet;

    /**
     * Flag how commits are handled
     *
     * @var bool
     */
    private $autoCommit = true;

    /**
     * Create a new instance
     * @param array $initialPreferences
     */
    public function __construct(array $initialPreferences)
    {
        $this->preferences = $initialPreferences;
        $this->changeSet = new ChangeSet();
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Attach an SplObserver
     * @link http://php.net/manual/en/splsubject.attach.php
     * @param SplObserver $observer <p>
     * The <b>SplObserver</b> to attach.
     * </p>
     * @return void
     */
    public function attach(SplObserver $observer)
    {
        $this->observer[] = $observer;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Detach an observer
     * @link http://php.net/manual/en/splsubject.detach.php
     * @param SplObserver $observer <p>
     * The <b>SplObserver</b> to detach.
     * </p>
     * @return void
     */
    public function detach(SplObserver $observer)
    {
        $key = array_search($observer, $this->observer, true);
        if ($key !== false) {
            unset($this->observer[$key]);
        }
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Notify an observer
     * @link http://php.net/manual/en/splsubject.notify.php
     * @return void
     */
    public function notify()
    {
        /** @var SplObserver $observer */
        $observer = null;
        foreach ($this->observer as $observer) {
            $observer->update($this);
        }
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     */
    public function count()
    {
        return count($this->preferences);
    }

    /**
     * Getter for change set
     * @return ChangeSet
     */
    public function getChangeSet()
    {
        return $this->changeSet;
    }

    public function has($key)
    {
        return array_key_exists($key, $this->preferences);
    }

    public function set($key, $value)
    {
        if ($this->has($key)) {
            $oldValue = $this->get($key);

            // Do not notify useless writes
            if ($oldValue !== $value) {
                $this->changeSet->appendUpdate($key, $value);
            }
        } else {
            $this->changeSet->appendCreate($key, $value);
        }

        $this->processCommit();
    }

    public function get($key, $default = null)
    {
        if ($this->has($key)) {
            return $this->preferences[$key];
        }

        return $default;
    }

    public function remove($key)
    {
        if ($this->has($key)) {
            $this->changeSet->appendDelete($key);
            $this->processCommit();
            return true;
        }

        return false;
    }

    public function startTransaction()
    {
        $this->autoCommit = false;
    }

    public function commit()
    {
        $changeSet = $this->changeSet;

        if ($this->autoCommit === false) {
            $this->autoCommit = true;
        }

        if ($changeSet->hasChanges() === true) {
            foreach ($changeSet->getCreate() as $key => $value) {
                $this->preferences[$key] = $value;
            }

            foreach ($changeSet->getUpdate() as $key => $value) {
                $this->preferences[$key] = $value;
            }

            foreach ($changeSet->getDelete() as $key) {
                unset($this->preferences[$key]);
            }

            $this->notify();

            $this->changeSet->clear();
        } else {
            throw new ProgrammingError('Nothing to commit');
        }
    }

    private function processCommit()
    {
        if ($this->autoCommit === true) {
            $this->commit();
        }
    }
}
