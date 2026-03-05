<?php
/* Icinga Web 2 | (c) 2018 Icinga Development Team | GPLv2+ */

namespace Tests\Icinga\Application\Hook;

use Icinga\Application\Hook\AuditHook;
use Icinga\Test\BaseTestCase;

class TestAuditHook extends AuditHook
{
    public function logMessage($time, $identity, $type, $message, ?array $data = null)
    {
        // TODO: Implement logMessage() method.
    }
}

class AuditHookTest extends BaseTestCase
{
    public function testFormatMessageResolvesFirstLevelParameters()
    {
        $this->assertEquals('foo', (new TestAuditHook())->formatMessage('{{test}}', ['test' => 'foo']));
    }

    public function testFormatMessageResolvesNestedLevelParameters()
    {
        $this->assertEquals('foo', (new TestAuditHook())->formatMessage('{{te.st}}', ['te' => ['st' => 'foo']]));
    }

    public function testFormatMessageResolvesParametersWithSingleBraces()
    {
        $this->assertEquals('foo', (new TestAuditHook())->formatMessage('{{t{e}st}}', ['t{e}st' => 'foo']));
        $this->assertEquals('foo', (new TestAuditHook())->formatMessage('{{te{.}st}}', ['te{' => ['}st' => 'foo']]));
    }

    public function testFormatMessageComplainsAboutUnresolvedParameters()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new TestAuditHook())->formatMessage('{{missing}}', []);
    }

    public function testFormatMessageComplainsAboutNonScalarParameters()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new TestAuditHook())->formatMessage('{{test}}', ['test' => ['foo' => 'bar']]);
    }

    public function testFormatMessageComplainsAboutNonArrayParameters()
    {
        $this->expectException(\InvalidArgumentException::class);

        (new TestAuditHook())->formatMessage('{{test.foo}}', ['test' => 'foo']);
    }
}
