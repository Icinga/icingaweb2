<?php
/* Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Modules\Monitoring\Web\Rest;

use Icinga\Exception\Json\JsonDecodeException;
use Icinga\Module\Monitoring\Web\Rest\RestRequest;
use Icinga\Test\BaseTestCase;

class MockedRestRequest extends RestRequest
{
    protected function curlExec(array $options)
    {
        return '<h1>Unauthorized</h1>';
    }
}

class RestRequestTest extends BaseTestCase
{
    /**
     * @expectedException JsonDecodeException
     */
    public function testInvalidServerResponseHandling()
    {
        MockedRestRequest::get('http://localhost')->send();
    }
}
