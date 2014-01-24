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

// @codingStandardsIgnoreStart
require_once realpath(__DIR__ . '/../../../../../library/Icinga/Test/BaseTestCase.php');
// @codingStandardsIgnoreEnd

use Icinga\Test\BaseTestCase;

// @codingStandardsIgnoreStart
require_once BaseTestCase::$libDir . '/Session/SessionNamespace.php';
// @codingStandardsIgnoreEnd

use \Exception;
use Icinga\Session\SessionNamespace;


class SessionNamespaceTest extends BaseTestCase
{
    /**
     * Check whether set, get, setAll and getAll works
     */
    public function testValueAccess()
    {
        $ns = new SessionNamespace();

        $ns->set('key1', 'val1');
        $ns->set('key2', 'val2');
        $this->assertEquals($ns->get('key1'), 'val1');
        $this->assertEquals($ns->get('key2'), 'val2');
        $this->assertEquals($ns->get('key3', 'val3'), 'val3');
        $this->assertNull($ns->get('key3'));

        $values = $ns->getAll();
        $this->assertEquals($values['key1'], 'val1');
        $this->assertEquals($values['key2'], 'val2');

        $new_values = array(
            'key1' => 'new1',
            'key2' => 'new2',
            'key3' => 'new3'
        );
        $ns->setAll($new_values);
        $this->assertEquals($ns->get('key1'), 'val1');
        $this->assertEquals($ns->get('key2'), 'val2');
        $this->assertEquals($ns->get('key3'), 'new3');
        $ns->setAll($new_values, true);
        $this->assertEquals($ns->get('key1'), 'new1');
        $this->assertEquals($ns->get('key2'), 'new2');
        $this->assertEquals($ns->get('key3'), 'new3');
    }

    /**
     * Check whether __set, __get, __isset and __unset works
     */
    public function testPropertyAccess()
    {
        $ns = new SessionNamespace();

        $ns->key1 = 'val1';
        $ns->key2 = 'val2';
        $this->assertEquals($ns->key1, 'val1');
        $this->assertEquals($ns->key2, 'val2');
        $this->assertEquals($ns->get('key1'), 'val1');

        $this->assertTrue(isset($ns->key1));
        $this->assertFalse(isset($ns->key3));

        unset($ns->key2);
        $this->assertFalse(isset($ns->key2));
        $this->assertNull($ns->get('key2'));
    }

    /**
     * @expectedException Exception
     */
    public function testFailingPropertyAccess()
    {
        $ns = new SessionNamespace();
        $ns->missing;
    }
}
