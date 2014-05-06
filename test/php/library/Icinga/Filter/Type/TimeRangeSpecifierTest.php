<?php
// {{{ICINGA_LICENSE_HEADER}}}
// {{{ICINGA_LICENSE_HEADER}}}

namespace Tests\Icinga\Filter;

use Icinga\Filter\Type\TimeRangeSpecifier;
use Icinga\Test\BaseTestCase;

class TimeRangeSpecifierTest extends BaseTestCase
{
    public function testIsValid()
    {
        $tRange = new TimeRangeSpecifier();
        $this->assertTrue(
            $tRange->isValidQuery('since yesterday'),
            'Assert "since yesterday" being a valid time range'
        );

        $this->assertTrue(
            $tRange->isValidQuery('since 2 days'),
            'Assert "since 2 days" being a valid time range'
        );

        $this->assertTrue(
            $tRange->isValidQuery('before tomorrow'),
            'Assert "before tomorrow" being a valid time range'
        );

        $this->assertTrue(
            $tRange->isValidQuery('since "2 hours"'),
            'Assert quotes being recognized'
        );
    }
}