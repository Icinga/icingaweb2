<?php
/* Icinga Web 2 | (c) 2014 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Regression;

use Icinga\Test\BaseTestCase;
use Icinga\Util\Translator;

/**
 * Regression-Test for bug #6432
 *
 * Translating strings must not throw an exception even if the given domain is not valid.
 *
 * @see https://dev.icinga.com/issues/6432
 */
class Bug6432Test extends BaseTestCase
{
    public function testWhetherTranslateReturnsTheInputStringInCaseTheGivenDomainIsNotValid()
    {
        $this->assertEquals('test', Translator::translate('test', 'invalid_domain'));
    }
}
