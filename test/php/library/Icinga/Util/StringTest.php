<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Util;

use Icinga\Test\BaseTestCase;
use Icinga\Util\String;

class StringTest extends BaseTestCase
{
    public function testWhetherTrimSplitReturnsACorrectValue()
    {
        $this->assertEquals(
            array('one', 'two', 'three'),
            String::trimSplit(' one ,two  , three'),
            'String::trimSplit does not properly split a string and/or trim its elements'
        );
    }

    public function testWhetherTrimSplitSplitsByTheGivenDelimiter()
    {
        $this->assertEquals(
            array('one', 'two', 'three'),
            String::trimSplit('one.two.three', '.'),
            'String::trimSplit does not split a string by the given delimiter'
        );
    }
}
