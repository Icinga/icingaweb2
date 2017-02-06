<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Data;

use Icinga\Test\BaseTestCase;
use Icinga\Data\DataArray\ArrayDatasource;

class ArrayDatasourceTest extends BaseTestCase
{
    private $sampleData;

    public function setUp()
    {
        parent::setUp();
        $this->sampleData = array(
            (object) array(
                'host'    => 'localhost',
                'problem' => '1',
                'service' => 'ping',
                'state'   => '2',
                'handled' => '1'
            ),
            (object) array(
                'host'    => 'localhost',
                'problem' => '1',
                'service' => 'www.icinga.com',
                'state'   => '0',
                'handled' => '0'
            ),
            (object) array(
                'host'    => 'localhost',
                'problem' => '1',
                'service' => 'www.icinga.com',
                'state'   => '1',
                'handled' => '0'
            )
        );
    }

    public function testSelectFactory()
    {
        $ds = new ArrayDatasource($this->sampleData);
        $query = $ds->select();
        $this->assertInstanceOf('Icinga\\Data\\SimpleQuery', $query);
    }
}
