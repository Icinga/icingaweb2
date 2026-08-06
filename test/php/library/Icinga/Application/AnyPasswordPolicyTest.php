<?php

namespace Tests\Icinga\Application;

use Icinga\User;
use PHPUnit\Framework\TestCase;
use Icinga\Application\ProvidedHook\AnyPasswordPolicy;

class AnyPasswordPolicyTest extends TestCase
{
    public function testValidatePasswordValid(): void
    {
        $this->assertEmpty((new AnyPasswordPolicy())->validate(new User('icingaadmin'), 'a'));
    }
}
