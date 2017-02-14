<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Web;

use Icinga\Forms\ConfigForm;
use Icinga\Test\BaseTestCase;

class ConfigFormTest extends BaseTestCase
{
    public function testWhetherTransformEmptyValuesToNullHandlesValuesCorrectly()
    {
        $values = array(
            'value1' => '',
            'value2' => 'this is a test',
            'value3' => array(),
            'value4' => array('Test1', 'Test2'),
            'value5' => 0,
            'value6' => 1
            );
        $values = ConfigForm::transformEmptyValuesToNull($values);

        $this->assertNull(
            $values['value1'],
            'ConfigForm::transformEmptyValuesToNull does not handle empty strings correctly'
        );
        $this->assertEquals(
            'this is a test',
            $values['value2'],
            'ConfigForm::transformEmptyValuesToNull does not handle strings correctly'
        );
        $this->assertNull(
            $values['value3'],
            'ConfigForm::transformEmptyValuesToNull does not handle empty arrays correctly'
        );
        $this->assertEquals(
            'Test1',
            $values['value4'][0],
            'ConfigForm::transformEmptyValuesToNull does not handle arrays correctly'
        );
        $this->assertEquals(
            0,
            $values['value5'],
            'ConfigForm::transformEmptyValuesToNull does not handle zeros correctly'
        );
        $this->assertEquals(
            1,
            $values['value6'],
            'ConfigForm::transformEmptyValuesToNull does not handle numbers correctly'
        );
    }
}
