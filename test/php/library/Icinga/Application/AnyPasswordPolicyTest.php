<?php

namespace Tests\Icinga\Application;

use Icinga\Authentication\PasswordPolicy;
use PHPUnit\Framework\TestCase;
use Icinga\Application\ProvidedHook\AnyPasswordPolicy;

class AnyPasswordPolicyTest extends TestCase
{
    private passwordPolicy $instance;

    public function setUp(): void
    {
        $this->instance = new AnyPasswordPolicy();
    }

    public function testValidatePasswordValid(): void
    {
        $this->assertEmpty($this->instance->validate('icingaadmin', 'null'));
    }
}
