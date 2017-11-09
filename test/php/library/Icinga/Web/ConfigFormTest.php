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
            'empty_string'      => '',
            'example_string'    => 'this is a test',
            'empty_array'       => array(),
            'example_array'     => array('test1', 'test2'),
            'zero_as_int'       => 0,
            'one_as_int'        => 1,
            'zero_as_string'    => '0',
            'one_as_string'     => '1',
            'bool_true'         => true,
            'bool_false'        => false,
            'null'              => null
        );

        $values = ConfigForm::transformEmptyValuesToNull($values);

        $this->assertNull(
            $values['empty_string'],
            'ConfigForm::transformEmptyValuesToNull() does not handle empty strings correctly'
        );

        $this->assertSame(
            'this is a test',
            $values['example_string'],
            'ConfigForm::transformEmptyValuesToNull() does not handle strings correctly'
        );

        $this->assertNull(
            $values['empty_array'],
            'ConfigForm::transformEmptyValuesToNull() does not handle empty arrays correctly'
        );

        $this->assertSame(
            'test1',
            $values['example_array'][0],
            'ConfigForm::transformEmptyValuesToNull() does not handle arrays correctly'
        );

        $this->assertSame(
            0,
            $values['zero_as_int'],
            'ConfigForm::transformEmptyValuesToNull() does not handle zeros correctly'
        );

        $this->assertSame(
            1,
            $values['one_as_int'],
            'ConfigForm::transformEmptyValuesToNull() does not handle numbers correctly'
        );

        $this->assertSame(
            '0',
            $values['zero_as_string'],
            'ConfigForm::transformEmptyValuesToNull() does not handle zeros correctly'
        );

        $this->assertSame(
            '1',
            $values['one_as_string'],
            'ConfigForm::transformEmptyValuesToNull() does not handle numbers correctly'
        );

        $this->assertSame(
            true,
            $values['bool_true'],
            'ConfigForm::transformEmptyValuesToNull() does not handle bool true correctly'
        );

        $this->assertNull(
            $values['bool_false'],
            'ConfigForm::transformEmptyValuesToNull() does not handle bool false correctly'
        );

        $this->assertNull(
            $values['null'],
            'ConfigForm::transformEmptyValuesToNull() does not handle null correctly'
        );
    }
}
