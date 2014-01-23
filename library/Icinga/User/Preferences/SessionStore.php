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

use Icinga\Session\Session;
use \SplObserver;
use \SplSubject;
use Icinga\User\Preferences;
use Icinga\Exception\ProgrammingError;

/**
 * Modify preferences into session
 */
class SessionStore implements SplObserver, LoadInterface
{
    /**
     * Name of session var for preferences
     */
    const DEFAULT_SESSION_NAMESPACE = 'preferences';

    /**
     * Session data
     *
     * @var Session
     */
    private $session;

    /**
     * Create a new object
     *
     * @param Session $session
     */
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Receive update from subject
     *
     * @link   http://php.net/manual/en/splobserver.update.php
     * @param  SplSubject $subject
     * @throws ProgrammingError
     */
    public function update(SplSubject $subject)
    {
        if (!$subject instanceof Preferences) {
            throw new ProgrammingError('Not compatible with '. get_class($subject));
        }

        $changeSet = $subject->getChangeSet();

        $data = $this->session->get(self::DEFAULT_SESSION_NAMESPACE, array());

        foreach ($changeSet->getCreate() as $key => $value) {
            $data[$key] = $value;
        }

        foreach ($changeSet->getUpdate() as $key => $value) {
            $data[$key] = $value;
        }

        foreach ($changeSet->getDelete() as $key) {
            unset($data[$key]);
        }

        $this->session->set(self::DEFAULT_SESSION_NAMESPACE, $data);

        $this->session->write();
    }

    /**
     * Public interface to copy all preferences into session
     *
     * @param array $preferences
     */
    public function writeAll(array $preferences)
    {
        $this->session->set(self::DEFAULT_SESSION_NAMESPACE, $preferences);
        $this->session->write();
    }

    /**
     * Load preferences from source
     *
     * @return array
     */
    public function load()
    {
        return $this->session->get(self::DEFAULT_SESSION_NAMESPACE, array());
    }
}
