<?php

namespace Tests\Icinga\Application;

use Icinga\Application\Hook\PasswordPolicyHook;
use Icinga\Test\BaseTestCase;
use Icinga\Application\ProvidedHook\NoPasswordPolicy;

class NoPasswordPolicyTest extends BaseTestCase
{
    private PasswordPolicyHook $instance;

    public function setUp(): void
    {
        $this->instance = new NoPasswordPolicy();
    }

    public function testMethodGetName(): void
    {
        $this->assertSame('None', $this->instance->getName());
    }

    public function testValidatePasswordValid(): void
    {
        $res = $this->instance->validatePassword('icingaadmin');
        $this->assertEmpty($res);
    }
}
