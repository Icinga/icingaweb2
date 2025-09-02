<?php

namespace Tests\Icinga\Application;

use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Test\BaseTestCase;
use Icinga\Application\ProvidedHook\NoPasswordPolicy;

class NoPasswordPolicyTest extends BaseTestCase
{
    private object $object;

    public function setUp(): void
    {
        $this->object = new NoPasswordPolicy();
    }

    public function testClassIsInstanzOf()
    {
        $this->assertInstanceOf(PasswordPolicyHook::class, $this->object);
    }

    public function testMethodGetName(): void
    {
        $this->assertSame('None', $this->object->getName());
    }

    public function testValidatePasswordValid()
    {
        $res = $this->object->validatePassword('icingaadmin');
        $this->assertEmpty($res);
    }
}
