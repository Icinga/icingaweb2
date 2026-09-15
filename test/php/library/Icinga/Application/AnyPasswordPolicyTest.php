<?php

namespace Tests\Icinga\Application;

use PHPUnit\Framework\TestCase;
use Icinga\Application\ProvidedHook\AnyPasswordPolicy;

class AnyPasswordPolicyTest extends TestCase
{
    public function testValidatePasswordValid(): void
    {
        $this->assertEmpty((new AnyPasswordPolicy())->validate('a'));
    }
}
