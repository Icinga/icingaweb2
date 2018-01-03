<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Regression;

use Icinga\Test\BaseTestCase;
use Icinga\Data\Filter\FilterQueryString;

/**
 * Icingaweb2 can't handle properly escaped URIs
 *
 * @see https://github.com/Icinga/icingaweb2/issues/3239
 */
class Bug3239Test extends BaseTestCase
{
    /**
     * Ensure an encoded filter does not throw any exceptions
     */
    public function testParseFilter()
    {
        FilterQueryString::parse('((service=a)%7C(service=b))');
    }
}
