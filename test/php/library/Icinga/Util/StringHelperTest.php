<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Util;

use Icinga\Test\BaseTestCase;
use Icinga\Util\StringHelper;

class StringTest extends BaseTestCase
{
    public function testWhetherTrimSplitReturnsACorrectValue()
    {
        $this->assertEquals(
            array('one', 'two', 'three'),
            StringHelper::trimSplit(' one ,two  , three'),
            'String::trimSplit does not properly split a string and/or trim its elements'
        );
    }

    public function testWhetherTrimSplitSplitsByTheGivenDelimiter()
    {
        $this->assertEquals(
            array('one', 'two', 'three'),
            StringHelper::trimSplit('one.two.three', '.'),
            'String::trimSplit does not split a string by the given delimiter'
        );
    }
}
