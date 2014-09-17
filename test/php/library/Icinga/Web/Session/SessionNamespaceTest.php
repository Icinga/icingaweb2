<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Web\Session;

use Icinga\Test\BaseTestCase;
use Icinga\Web\Session\SessionNamespace;

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
     * @expectedException Icinga\Exception\IcingaException
     */
    public function testFailingPropertyAccess()
    {
        $ns = new SessionNamespace();
        $ns->missing;
    }

    /**
     * Check whether iterating over session namespaces works
     */
    public function testIteration()
    {
        $ns = new SessionNamespace();
        $values = array('key1' => 'val1', 'key2' => 'val2');
        $ns->setAll($values);
        foreach ($ns as $key => $value) {
            $this->assertEquals($value, $values[$key]);
        }
    }
}
