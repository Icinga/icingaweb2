<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Util;

use Icinga\Util\ASN1;
use Icinga\Test\BaseTestCase;
use InvalidArgumentException;

class ASN1Test extends BaseTestCase
{
    public function testAllValidGeneralizedTimeCombinations()
    {
        foreach (array('', '04', '0405', '0460') as $is) {
            foreach (array('', ',7890', '.7890') as $frac) {
                foreach (array('Z', '-07', '-0742', '+07', '+0742') as $tz) {
                    $this->assertValidGeneralizedTime("1970010203$is$frac$tz");
                }
            }
        }
    }

    public function testAllGeneralizedTimeRangeBorders()
    {
        $this->assertBadGeneralizedTime('1970000203Z');
        $this->assertValidGeneralizedTime('1970010203Z');
        $this->assertValidGeneralizedTime('1970120203Z');
        $this->assertBadGeneralizedTime('1970130203Z');

        $this->assertBadGeneralizedTime('1970010003Z');
        $this->assertValidGeneralizedTime('1970010103Z');
        $this->assertValidGeneralizedTime('1970013103Z');
        $this->assertBadGeneralizedTime('1970013203Z');

        $this->assertValidGeneralizedTime('1970010200Z');
        $this->assertValidGeneralizedTime('1970010223Z');
        $this->assertBadGeneralizedTime('1970010224Z');

        $this->assertValidGeneralizedTime('197001020300Z');
        $this->assertValidGeneralizedTime('197001020359Z');
        $this->assertBadGeneralizedTime('197001020360Z');

        $this->assertValidGeneralizedTime('19700102030400Z');
        $this->assertValidGeneralizedTime('19700102030460Z');
        $this->assertBadGeneralizedTime('19700102030461Z');

        foreach (array('-', '+') as $sign) {
            $this->assertValidGeneralizedTime("1970010203{$sign}00");
            $this->assertValidGeneralizedTime("1970010203{$sign}23");
            $this->assertBadGeneralizedTime("1970010203{$sign}24");

            $this->assertValidGeneralizedTime("1970010203{$sign}0000");
            $this->assertValidGeneralizedTime("1970010203{$sign}0059");
            $this->assertBadGeneralizedTime("1970010203{$sign}0060");
        }
    }

    public function testGeneralizedTimeFractions()
    {
        $this->assertGeneralizedTimeDiff('19700102030405.9Z', '19700102030405Z', 1);
        $this->assertGeneralizedTimeDiff('197001020304.5Z', '197001020304Z', 30);
        $this->assertGeneralizedTimeDiff('1970010203.5Z', '1970010203Z', 1800);
    }

    protected function assertValidGeneralizedTime($value)
    {
        try {
            $dateTime = ASN1::parseGeneralizedTime($value);
        } catch (InvalidArgumentException $e) {
            $dateTime = null;
        }

        $this->assertInstanceOf(
            '\DateTime',
            $dateTime,
            'Failed asserting that ' . var_export($value, true) . ' is a valid date/time'
        );
    }

    protected function assertBadGeneralizedTime($value)
    {
        $valid = true;

        try {
            ASN1::parseGeneralizedTime($value);
        } catch (InvalidArgumentException $e) {
            $valid = false;
        }

        $this->assertFalse($valid, 'Failed asserting that ' . var_export($value, true) . ' is not a valid date/time');
    }

    protected function assertGeneralizedTimeDiff($lhs, $rhs, $seconds)
    {
        $this->assertSame(
            ASN1::parseGeneralizedTime($lhs)->getTimestamp() - ASN1::parseGeneralizedTime($rhs)->getTimestamp(),
            $seconds,
            'Failed asserting that ' . var_export($lhs, true) . ' and ' . var_export($rhs, true)
                . " differ by $seconds seconds"
        );
    }
}
