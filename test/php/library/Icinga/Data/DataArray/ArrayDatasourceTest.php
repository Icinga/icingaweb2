<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

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
                'service' => 'www.icinga.org',
                'state'   => '0',
                'handled' => '0'
            ),
            (object) array(
                'host'    => 'localhost',
                'problem' => '1',
                'service' => 'www.icinga.org',
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
