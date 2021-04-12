<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Data;

use Icinga\Test\BaseTestCase;
use Icinga\Data\DataArray\ArrayDatasource;

class ArrayDatasourceTest extends BaseTestCase
{
    private $query;

    public function setUp(): void
    {
        parent::setUp();
        $this->query = (new ArrayDatasource([
            (object) [
                'host'    => 'a host',
                'problem' => '1',
                'state'   => '2',
                'handled' => '1'
            ],
            (object) [
                'host'    => 'b host',
                'problem' => '1',
                'state'   => '0',
                'handled' => '0'
            ],
            (object) [
                'host'    => 'c host',
                'problem' => '1',
                'state'   => '1',
                'handled' => '0'
            ]
        ]))->select();
    }

    public function testSelectFactory()
    {
        $this->assertInstanceOf('Icinga\\Data\\SimpleQuery', $this->query);
    }

    public function testOrderWithOneRuleIsCorrect()
    {
        $result = $this->query
            ->order('host', 'desc')
            ->fetchAll();

        $this->assertEquals(
            [
                (object) [
                    'host'    => 'c host',
                    'problem' => '1',
                    'state'   => '1',
                    'handled' => '0'
                ],
                (object) [
                    'host'    => 'b host',
                    'problem' => '1',
                    'state'   => '0',
                    'handled' => '0'
                ],
                (object) [
                    'host'    => 'a host',
                    'problem' => '1',
                    'state'   => '2',
                    'handled' => '1'
                ]
            ],
            $result,
            'ArrayDatasource does not sort queries correctly'
        );
    }

    public function testOrderWithTwoRulesIsCorrect()
    {
        $result = $this->query
            ->order('handled', 'asc')
            ->order('host', 'asc')
            ->fetchAll();

        $this->assertEquals(
            [
                (object) [
                    'host'    => 'b host',
                    'problem' => '1',
                    'state'   => '0',
                    'handled' => '0'
                ],
                (object) [
                    'host'    => 'c host',
                    'problem' => '1',
                    'state'   => '1',
                    'handled' => '0'
                ],
                (object) [
                    'host'    => 'a host',
                    'problem' => '1',
                    'state'   => '2',
                    'handled' => '1'
                ]
            ],
            $result,
            'ArrayDatasource does not sort queries correctly'
        );
    }

    public function testOrderIsCorrectWithLimitAndOffset()
    {
        $result = $this->query
            ->order('handled', 'asc')
            ->order('host', 'asc')
            ->limit(2)
            ->fetchAll();

        $this->assertEquals(
            [
                (object) [
                    'host'    => 'b host',
                    'problem' => '1',
                    'state'   => '0',
                    'handled' => '0'
                ],
                (object) [
                    'host'    => 'c host',
                    'problem' => '1',
                    'state'   => '1',
                    'handled' => '0'
                ]
            ],
            $result,
            'ArrayDatasource does not sort limited queries correctly'
        );
    }
}
