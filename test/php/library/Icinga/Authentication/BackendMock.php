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

namespace Tests\Icinga\Authentication;

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');
// @codingStandardsIgnoreEnd

use Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once 'Zend/Config.php';
require_once BaseTestCase::$libDir . '/Authentication/Credential.php';
require_once BaseTestCase::$libDir . '/Authentication/UserBackend.php';
require_once BaseTestCase::$libDir . '/User.php';
// @codingStandardsIgnoreEnd

use \Zend_Config;
use \Icinga\Authentication\Credential;
use \Icinga\Authentication\UserBackend as UserBackend;
use \Icinga\User;

/**
*   Simple backend mock that takes an config object
*   with the property "credentials", which is an array
*   of Credential this backend authenticates
**/
class BackendMock implements UserBackend
{
    public $allowedCredentials = array();
    public $name;

    public function __construct(Zend_Config $config = null)
    {
        if ($config === null) {
            return;
        }

        if (isset ($config->credentials)) {
            $this->allowedCredentials = $config->credentials;
        }

        if ($config->name) {
            $this->name = $config->name;
        } else {
            $this->name = 'TestBackendMock-' . uniqid();
        }
    }

    public function hasUsername(Credential $userCredentials)
    {
        foreach ($this->allowedCredentials as $credential) {
            if ($credential->getUsername() == $userCredentials->getUsername()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Name of the backend
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public static function getDummyUser()
    {
        return new User(
            'Username',
            'Firstname',
            'Lastname',
            'user@test.local'
        );
    }

    public function getUserCount() {
         return count($this->allowedCredentials);
    }

    public function authenticate(Credential $credentials)
    {
        if (!in_array($credentials, $this->allowedCredentials)) {
            return false;
        }

        return self::getDummyUser();
    }

    public function setCredentials(array $credentials)
    {
        $this->allowedCredentials = $credentials;
    }
}
